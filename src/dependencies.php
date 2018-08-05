<?php
use \Zend\Permissions\Acl\Acl;

// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($container) {
    $settings = $container->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};
$container['twig_profile'] = function () {
    return new Twig_Profiler_Profile();
};
$container['twig'] = function($container) {
    $config = $container->get('settings');
    $view = new \Slim\Views\Twig(
        $config['twig']['template_path'],
        $config['twig']['twig']
    );
    $basePath = base_path($container['request']);
    $view->addExtension(
        new \Slim\Views\TwigExtension($container['router'], $basePath)
    );
    return $view;
};

// monolog
$container['logger'] = function($container) {
    $settings = $container->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// 全局变量读取器
$container['globals'] = function($container) {
    return new \GlobalVars();
};

// 配置文件存储
$container['file_store'] = function($container) {
    // fix php pathinfo() utf-8 bug
    // setlocale(LC_ALL, 'zh_CN.UTF-8');
    $store_cfg = $container['settings']['store'];
    $local_cfg = array_get($store_cfg, 'local');
    if(is_array($local_cfg)) {
        // Create the adapter
        // Linux File/Directory Permissions: http://linuxcommand.org/lts0070.php
        $localAdapter = new League\Flysystem\Adapter\Local(
            full_path($local_cfg['path']),
            \LOCK_EX,
            League\Flysystem\Adapter\Local::SKIP_LINKS,
            [
                'file' => [
                    'public' => 0666, // rw-rw-rw-
                    'private' => 0666,
                ],
                'dir' => [
                    'public' => 0777, // rwxrwxrwx
                    'private' => 0777,
                ]
            ]
        );
        // Create the cache store
        $cacheStore = new League\Flysystem\Cached\Storage\Memory();
        // Decorate the adapter
        $adapter = new League\Flysystem\Cached\CachedAdapter($localAdapter, $cacheStore);
        // And use that to create the file system
        $fs = new League\Flysystem\Filesystem($adapter);
        // $fs->addPlugin(new League\Flysystem\Plugin\ListPaths());
        return $fs;
    }

    throw new Exception("settings.store or settings.use_store 配置有误", 1);
    
};