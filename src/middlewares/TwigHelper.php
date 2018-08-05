<?php

namespace App\Middlewares;

/**
 * 针对Twig模板的一些操作
 */
class TwigHelper extends Base {

    protected $router;

    public function __invoke(\Slim\Http\Request $request, \Slim\Http\Response $response, callable  $next) {
        $this->router = $this->container->get('router');
        $this->request = $request;
        $view = $this->container->get('twig');
        $env = $view->getEnvironment();

        // 增加全局模板变量
        // $twig->addGlobal('some_data', ['a' => ['b', 'c']]);

        // 增加额外模板变量
        // $view->offsetSet('show_debug_info', true);

        // 注册Filter(筛选器)
        $this->register_filters($env);

        // 注册Function(函数)
        $this->register_functions($env);

        $response = $next($request, $response);
        return $response;
    }

    // 将变量转换为字符串(否则数组值会出错)
    public static function filter_stringfy($value) {
        if(is_array($value)) {
            $data = print_r($value, true);
            $data = str_replace('<', '&lt;', $data);
            $data = str_replace('>', '&gt;', $data);
            return '<pre>' . $data . '</pre>';
        }
        $value = str_replace('<', '&lt;', $value);
        $value = str_replace('>', '&gt;', $value);
        return $value;
    }

    // 判断是否是用户变量(去掉模板子级增加的变量)
    public static function filter_is_var($key) {
        return !in_array($key, ['_parent']);
    }

    // 函数-->Filter转换(根据方法名得到Filter)
    private static function convert_filter($filter_name, $function_name) {
        $func = '\\' . __CLASS__ . '::' . $function_name;
        $prefix = 'gsw_';
        $options = [
            'is_safe' => true
        ];
        return new \Twig_Filter($prefix . $filter_name, $func, $options);
    }

    // 注册Filter
    public function register_filters($environment) {
        $filters = [
            static::convert_filter('stringfy', 'filter_stringfy'),
            static::convert_filter('is_var', 'filter_is_var')
        ];
        foreach ($filters as $filter) {
            $environment->addFilter($filter);
        }
        // foreach ($environment->getFilters() as $item) {
        //     var_dump($item->getName());
        // }
    }

    // 合并url, 支持带随机参数
    public static function merge_url_rand($base_path, $url, $add_rand=false, $param_key='t') {
        $url = merge_url($base_path, $url);
        if($add_rand) {
            $rand = $add_rand ? sprintf($param_key . '=%.5f', microtime(true)) : '';
            $sp = '?';
            if(strpos($url, '?')  !== false) {
                $sp = '&';
            }
            $url = $url . $sp . $rand;
        }
        return $url;
        
    }

    // 将相对地址转换为觉得地址
    public static function raw_function_full_url($request) {
        $prefix = 'gsw_';
        $function_name = 'full_url';
        $func = '\\' . __CLASS__ . '::merge_url_rand';
        $base_path = base_path($request);
        $options = [
            'is_safe' => ['all']
        ];
        return new \Twig_Function($prefix . $function_name, function($url, $add_rand=false, $param_key='t') use($base_path, $func) {
            return $func($base_path, $url, $add_rand, $param_key);
        }, $options);
    }

    // 函数-->Function转换(根据方法名得到Function)
    private static function convert_function($filter_name, $function_name) {
        $func = '\\' . __CLASS__ . '::' . $function_name;
        $prefix = 'gsw_';
        $options = [
            'is_safe' => ['all'],
            'needs_context' => true
        ];
        return new \Twig_Function($prefix . $filter_name, $func, $options);
    }

    // 注册Function
    public function register_functions($environment) {
        $functions = [
            // static::convert_function('full_url', 'function_full_url')
        ];
        foreach ($functions as $function) {
            $environment->addFunction($function);
        }
        $environment->addFunction(static::raw_function_full_url($this->request));
        // foreach ($environment->getFunctions() as $item) {
        //     var_dump($item->getName());
        // }
    }
}
