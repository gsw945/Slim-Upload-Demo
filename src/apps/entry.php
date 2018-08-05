<?php

return [
    'api' => [
        'prefix' => '/file',
        'urls' => [
            '/single[/]' => [
                'get' => [
                    'handler' => 'App\Views\FileView:single',
                    'name' => 'file_single_upload'
                ],
            ],
            '/multiple[/]' => [
                'get' => [
                    'handler' => 'App\Views\FileView:multiple',
                    'name' => 'file_multiple_upload'
                ],
            ],
            '/upload[/]' => [
                'post' => [
                    'handler' => 'App\Controllers\FileController:upload',
                    'name' => 'file_upload',
                ],
            ],
            '/visit[/[{filename}]]' => [
                'get' => [
                    'handler' => 'App\Controllers\FileController:visit',
                    'name' => 'file_visit'
                ],
            ],
        ]
    ],
    'home' => [
        'prefix' => '',
        'urls' => [
            '/[home[/]]' => [
                'get' => [
                    'handler' => function(\Slim\Http\Request $request, \Slim\Http\Response $response, $args=[]) {
                        $router = $this->get('router');
                        $single_upload_url = $router->pathFor('file_single_upload');
                        $response->getBody()->write("<a href=\"{$single_upload_url}\">单文件上传</a>");
                        return $response;
                    },
                    'name' => 'site_home',
                    'methods' => ['GET', 'POST']
                ],
            ],
        ]
    ]
];
