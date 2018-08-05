<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * FileController 操作
 */
class FileController extends ControllerBase {
    
    // 断点重传
    public function resume_upload(Request $request, Response $response, $args=[]) {
        $params = $request->getParams();
        $file_name = array_get($params, 'file');
        $size = 0;
        if(file_exists($file_name)) {
            $size = filesize($file_name);
        }
        $ret = [
            'size' => $size
        ];
        return $response->withJson($ret);
    }

    // 访问文件
    public function visit(Request $request, Response $response, $args=[]) {
        $params = $request->getParams();
        $filename = array_get($args, 'filename');
        if(empty($filename)) {
            $filename = array_get($params, 'filename');
        }
        if(!empty($filename)) {
            // 直接重定向
            $store_cfg = $this->ci->get('settings')['store'];
            $use_store = $this->ci->get('settings')['use_store'];
            $store_cfg = $store_cfg[$use_store];
            $visit_base = array_get($store_cfg, 'visit', null);
            if(!empty($visit_base)) {
                $visit_bucket = $store_cfg['bucket'];
                $visit_path = merge_url($visit_base, $visit_bucket);
                $visit_url = merge_url($visit_path, $filename);
                $response->getBody()->rewind();
                // $f_params = sprintf('?filename=%s', $filename);
                // $uri = $uri->withQuery($f_params);
                return $response->withRedirect($visit_url, 301);
            }
            // 读取文件
            $uploader = $this->file_store;
            if($uploader->has($filename)) {
                $mime = $uploader->getMimetype($filename);
                $file_length = $uploader->getSize($filename);
                $response = $response->withHeader('Content-Type', $mime);
                $response = $response->withHeader('Content-Length', $file_length);
                $stream = $uploader->readStream($filename);
                return $response->withBody(new \Slim\Http\Stream($stream));
            }
        }
        $base_url = base_path($request);
        if(empty($base_url)) {
            $base_url = '/';
        }
        return $this->ci->get('twig')->render(
            $response->withStatus(404),
            '_error/404.twig',
            [
                'base_url' => $base_url
            ]
        );
    }

    // 上传文件
    public function upload(Request $request, Response $response, $args=[]) {
        $files = $request->getUploadedFiles();
        $params = $request->getParams();
        // input[type="file"] 表单 name
        $field = array_get($params, 'field');
        // 客户端文件唯一标识
        $client_hash = array_get($params, 'hash');
        $ret = [];
        $status = 200;
        if(empty($field) || empty($client_hash)) {
            $ret = [
                'error' => 1,
                'desc' => '参数缺失'
            ];
        }
        else {
            $file = @$files[$field];
            if(empty($file)) {
                $ret = [
                    'error' => 2,
                    'desc' => '文件未找到'
                ];
                $status = 400;
            }
            else {
                // 获取上传的配置.
                $upload_cfg = $this->ci->get('settings')['upload'];
                // 获取密钥.
                $secret = $this->ci->get('settings')['secret'];
                // 这里是分片上传的内容大小.
                $range_str = $request->getHeaderLine('Content-Range');
                // 获取日志的对象.
                $logger = $this->ci->get('logger');
                $uploader = $this->file_store;
                $ret = static::upload_handle($uploader, $file, $client_hash, $range_str, $upload_cfg, $secret, $logger);
            }
        }
        return $response->withJson($ret)->withStatus($status);
    }

    /**
     * 上传检查
     */
    private static function check_upload($cfg_types, $file_ext, $file_size, $file_major) {
        $ext_arr = [];
        $size_arr = [];
        $major_arr = [];
        $ok_item = array_filter($cfg_types, function($type_item) use($file_ext, $file_size, $file_major, &$ext_arr, &$size_arr, &$major_arr) {
            $ext_ok = in_array($file_ext, $type_item['exts']);
            $ext_arr[] = $ext_ok;
            $size_ok = ($file_size <= $type_item['size'] && $file_size >= 0);
            $size_arr[] = $size_ok;
            $major_ok = in_array($file_major, $type_item['major']);
            $major_arr[] = $major_ok;
            return $ext_ok && $size_ok && $major_ok;
        });
        $all_count = count($cfg_types);
        $ext_count = count(array_filter($ext_arr, function($item) {
            return $item == false;
        }));
        $size_count = count(array_filter($size_arr, function($item) {
            return $item == false;
        }));
        $major_count = count(array_filter($major_arr, function($item) {
            return $item == false;
        }));
        unset($ext_arr);
        unset($size_arr);
        unset($major_arr);
        $desc = null;
        if($ext_count != $all_count) {
            $desc = '不允许的文件后缀名';
        }
        else if($size_count != $all_count) {
            $desc = '不允许的文件大小';
        }
        else if($major_count != $all_count) {
            $desc = '不允许的文件类型';
        }
        return [
            'ok' => !empty($ok_item),
            'desc' => $desc
        ];
    }

    
    // 文件上传处理
    private static function upload_handle($uploader, $file, $client_hash, $range_str, $upload_cfg, $secret, $logger) {
        $ret = [];
        if($file instanceof \Psr\Http\Message\UploadedFileInterface) {
            if($file->getError() === \UPLOAD_ERR_OK) {
                $file_name = $file->getClientFilename();
                $origin_file = $file_name;
                $file_size = $file->getSize();
                $file_mime = $file->getClientMediaType();

                $split = explode('/', $file_mime);
                $file_major = $split[0];
                $file_ext = pathinfo($file_name, \PATHINFO_EXTENSION);
                // $file_base = pathinfo($file_name, \PATHINFO_FILENAME);
                // 验证文件后缀名、类型、大小是否符合要求
                $checked = static::check_upload($upload_cfg['types'], $file_ext, $file_size, $file_major);
                if($checked['ok']) {
                    $cache_folder = full_path($upload_cfg['cache']);
                    if(!is_dir($cache_folder)) {
                        mkdir($cache_folder, null, true);
                        chmod($cache_folder, 0777);
                    }
                    // 根据客户端(浏览器)传递的hash值生成文件名
                    $new_name = $client_hash . '@' . $file_name;
                    $path = merge_path($cache_folder, $new_name);
                    $is_chunked = false;
                    $range_pattern = '|^bytes (\d+)-(\d+)\/(\d+)$|i';
                    $_range = [];
                    if(!empty($range_str)) {
                        $is_mat = preg_match($range_pattern, $range_str, $matches);
                        if($is_mat && is_array($matches) && count($matches) > 3) {
                            $_range['begin'] = intval($matches[1]);
                            $_range['end'] = intval($matches[2]);
                            $_range['total'] = intval($matches[3]);
                            $is_chunked = true;
                        }
                    }
                    if($is_chunked) {
                        // 分片处理
                        $fp = fopen($path, 'a');
                        fseek($fp, $_range['begin'], \SEEK_SET);
                        fwrite($fp, $file->getStream());
                        fclose($fp);
                        // 这里要设置权限.
                        chmod($path,0777);
                    }
                    else {
                        // 未分片
                        $file->moveTo($path);
                        // 这里设置权限
                        chmod($path,0777);
                    }
                    $mt = new \Pekkis\MimeTypes\MimeTypes();
                    $real_mime = $mt->resolveMimeType($path);
                        $completed = false;
                        if(file_exists($path) && is_file($path)) {
                            if($is_chunked) {
                                //清除缓存并再次检查文件大小
                                clearstatcache();
                                $real_file_size = filesize($path);
                                if($real_file_size >= $_range['total'] - 1) {
                                    $completed = true;
                                }
                            }
                            else {
                                $completed = true;
                            }
                        }
                        if($completed) {
                            // 上传
                            $stream = fopen($path, 'r+');
                            $uploader->writeStream($new_name, $stream);
                            if (is_resource($stream)) {
                                fclose($stream);
                                unlink($path);
                            }
                            $ret = [
                                'error' => 0,
                                'desc' => '上传完成',
                                'url' => $new_name
                            ];
                        }
                        else {
                            $ret = [
                                'error' => 0,
                                'desc' => '上传成功'
                            ];
                        }
                        $ret_common = [
                            'name' => $origin_file, // 上传的文件名
                            'size' => $file_size, // 文件已上传的大小
                            'type' => $file_mime // 文件类型
                        ];
                        $ret = array_merge($ret, $ret_common);
                }
                else {
                    $ret = [
                        'error' => 3,
                        'desc' => $checked['desc']
                    ];
                }
            }
            else {
                $ret = [
                    'error' => 2,
                    'desc' => '上传错误'
                ];
            }
        }
        else {
            $ret = [
                'error' => 1,
                'desc' => '获取文件失败, 请重试'
            ];
        }
        return $ret;
    }
}
