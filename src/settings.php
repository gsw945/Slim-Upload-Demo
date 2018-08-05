<?php
$twig_cache_dir = PROJ_BASE_DIR . '/cache/twig';
$logs_dir = PROJ_BASE_DIR . '/logs';
$local_store = PROJ_BASE_DIR . '/store';

$debug = true;
$tracy_debug = true;
$mode = 'production';

if($debug) {
    $mode = 'development';
    if(!is_dir($twig_cache_dir)) {
        mkdir($twig_cache_dir, 0777, true);
        chmod($twig_cache_dir, 0777);
    }
    if(!is_dir($logs_dir)) {
        mkdir($logs_dir, 0777, true);
        chmod($logs_dir, 0777);
    }
    if(!is_dir($local_store)) {
        mkdir($local_store, 0777, true);
        chmod($local_store, 0777);
    }
    clearstatcache();
}
return [
    'settings' => [
        'determineRouteBeforeAppMiddleware' => true, # https://github.com/slimphp/Slim/issues/1505
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // !!$tracy_debug, // Allow the web server to send the content-length header

        // debug
        'debug' => $debug,
        'mode' => $mode,

        // CORS 
        'cors' => true,

        // Renderer settings
        'twig' => [
            'template_path' => PROJ_BASE_DIR . '/templates',
            'twig' => [
                'cache' => $twig_cache_dir,
                'debug' => true,
                'auto_reload' => true
            ]
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => $logs_dir . '/app_' . date('Y-m', time()) . '.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // store
        'store' => [
            'local' => [
                'path' => $local_store
            ]
        ],

        // upload
        'upload' => [
            'types' => [
                // 后缀
                // 尺寸，单位: Byte
                // 文件mime主要类型
                'web_img' => [
                    'exts' => ['png', 'jpeg', 'jpg', 'gif', 'bmp'], // web图片
                    'size' => 1024 * 1024 * 6, // 6 MB
                    'major' => ['image']
                ],
                'archive' => [
                    'exts' => ['rar', 'zip', '7z', 'x-rar'], //压缩文件
                    'size' => 1024 * 1024 * 500, // 500 MB
                    'major' => ['application']
                ],
                'other' => [
                    'exts' => [], // 其他
                    'size' => 1024 * 1024 * 100, // 100 MB
                    'major' => []
                ]
            ],
            'cache' => PROJ_BASE_DIR . '/cache/upload/'
        ],

        // public path
        'public_path' => PROJ_BASE_DIR . '/public/',

        // secret key
        'secret' => 'secret string for slim app',
    ],
];
