<?php

namespace App\Middlewares;

/**
 * 开发-文件合并
 */
class DevMerge extends Base {

    public function __invoke(\Slim\Http\Request $request, \Slim\Http\Response $response, callable  $next) {
        $need_merge = ($request->isGet() && $request->isXhr() == false);
        $config = $this->container->get('settings');
        // 只有在开发阶段才需要执行
        if($need_merge && $config['debug']) {
            $public_dir = full_path($config['public_path']);
            $merge_json = merge_path($public_dir, 'assets/merge.json');
            if(file_exists($merge_json)) {
                $merge_dir = dirname($merge_json);
                $json_raw = file_get_contents($merge_json);
                $verify = text_is_json($json_raw);
                if($verify['ok']) {
                    $json_data = json_decode($json_raw, true);
                    foreach ($json_data as $item_base => $item_subs) {
                        $item_dir = merge_path($merge_dir, $item_base);
                        foreach ($item_subs as $sub_item) {
                            $target = $sub_item['target'];
                            $target = merge_path($item_dir, $target);
                            $content_main = [];
                            $files = $sub_item['files'];
                            $type = $sub_item['type'];
                            foreach ($files as $file_item) {
                                if(starts_with($file_item, '@')) {
                                    $file_item = substr($file_item, 1);
                                    $file_path = merge_path($merge_dir, $file_item);
                                }
                                else {
                                    $file_path = merge_path($item_dir, $file_item);
                                }
                                if($type == 'js') {
                                    $content_main[] = "// " . $file_item;
                                }
                                else if($type == 'css') {
                                    $content_main[] = "/* " . $file_item . " */";
                                }
                                $content_main[] = file_get_contents($file_path);
                            }
                            // 必须换行，因为js存在单行注释
                            $content_main = implode("\n", $content_main);
                            if($type == 'js') {
                                $import = $sub_item['require_import'];
                                $export = $sub_item['require_export'];
                                $target_content = implode('', [
                                    "define([" . $import . "], function(" . $export . ") {\n",
                                    "   'use strict';\n",
                                    "   $(function() {\n",
                                    "      ". $content_main . "\n",
                                    "   });\n",
                                    "});"
                                ]);
                            }
                            else if($type == 'css') {
                                $target_content = $content_main;
                            }
                            file_put_contents($target, $target_content);
                        }
                    }
                }
                else {
                    echo "<pre>";
                    echo "<strong>" . __FILE__ . "</strong>\n";
                    echo "Debug Info: json file verification failed";
                    echo "<hr />";
                    echo "<strong style=\"color: #1cf;\">" . $merge_json . "</strong>\n";
                    echo "Error Message: <code style=\"color: #f00;\">" . $verify['error'] . "</code>";
                    echo "</pre>";
                    die();
                }
            }
        }


        $response = $next($request, $response);
        return $response;
    }
}
