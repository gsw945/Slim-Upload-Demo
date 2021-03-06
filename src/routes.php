<?php
// Routes

$sub_apps = require __DIR__ . '/apps/entry.php';

$settings = $container->get('settings');

$i = 0;
foreach ($sub_apps as $key => $sub_app) {
    $prefix = $sub_app['prefix'];
    $urls = $sub_app['urls'];
    foreach ($urls as $url => $action) {
        foreach ($action as $method => $content) {
            $handler = $content['handler'];
            $route = $prefix . $url;
            $id = null;
            switch (strtolower($method)) {
                case 'get':
                    $id = $app->get($route, $handler);
                    break;
                case 'post':
                    $id = $app->post($route, $handler);
                    break;
                case 'put':
                    $id = $app->put($route, $handler);
                    break;
                case 'delete':
                    $id = $app->delete($route, $handler);
                    break;
                case 'head':
                    $id = $app->head($route, $handler);
                    break;
                case 'patch':
                    $id = $app->patch($route, $handler);
                    break;
                case 'options':
                    $id = $app->options($route, $handler);
                    break;
                # --------------------------------------------
                case 'any':
                    $id = $app->any($route, $handler);
                    break;
                case 'map':
                    if(array_key_exists('methods', $content)) {
                        $methods = $content['methods'];
                        if(!empty($methods)) {
                            $methods = array_map('strtoupper', $methods);
                            $id = $app->map($methods, $route, $handler);
                        }
                        else {
                            echo 'map method need methods with not empty';
                        }
                    }
                    else {
                        echo 'map method need methods';
                    }
                    break;
                default:
                    # code...
                    echo 'http request method not support';
                    break;
            }
            if(isset($id)) {
                $name = null;
                if(array_key_exists('name', $content)) {
                    $name = $content['name'];
                }
                if(!isset($name)) {
                    $name = 'route' . $i;
                }
                $id->setName($name);
            }
            $i += 1;
        }
    }
}