<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

defined('PROJ_BASE_DIR') || define('PROJ_BASE_DIR', realpath(dirname(__DIR__)));

require PROJ_BASE_DIR . '/src/set_env.php';
require PROJ_BASE_DIR . '/vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require PROJ_BASE_DIR . '/src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require PROJ_BASE_DIR . '/src/dependencies.php';

// Register middleware
require PROJ_BASE_DIR . '/src/middleware.php';

// Register routes
require PROJ_BASE_DIR . '/src/routes.php';

// Run app
$app->run();
