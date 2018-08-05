<?php
namespace App\Controllers;

/**
 * ControllerBase
 */
class ControllerBase
{
    protected $ci;

    /**
     * @var \League\Flysystem\Filesystem
     */
    protected $file_store = null;

    public function __construct(\Interop\Container\ContainerInterface $ci)
    {
        $this->ci = $ci;
        // 加载Local FileSystem容器
        $this->file_store = $ci->get('file_store');
    }

    /**
     * 获取正确的值.
     * @param $key
     * @param $param
     * @param array $args
     * @return mixed|null
     */
    public function filter_param($key,$param,$args = []){
        if(array_key_exists($key,$param)){
            return $param[$key];
        }
        if($args){
            if(array_key_exists($key,$args)){
                return array_get($args,$key,null);
            }
        }
        return null;
    }
}