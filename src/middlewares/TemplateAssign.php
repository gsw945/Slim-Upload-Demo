<?php

namespace App\Middlewares;

/**
 * 模板分配中间件
 */
class TemplateAssign extends Base {

    public function __invoke(\Slim\Http\Request $request, \Slim\Http\Response $response, callable  $next) {
        $router = $this->container->get('router');
        $view = $this->container->get('twig');
        $settings = $this->container->get('settings');
        $vars = [];

        $vars['platform'] = [
            'name' => '文件上传'
        ];

        foreach ($vars as $key => $value) {
            // $view->getEnvironment()->addGlobal($key, $value); // assign variable(style 1)
            $view->offsetSet($key, $value); // assign variable(style 2)
        }

        $response = $next($request, $response);
        return $response;
    }
}
