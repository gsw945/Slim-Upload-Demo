<?php
// Application middleware

$settings = $container->get('settings');
$app->add(new \App\Middlewares\TemplateAssign($app));  // 5
$app->add(new \App\Middlewares\TwigHelper($app));  // 4

if($settings['cors']) {
    $app->add(new \App\Middlewares\CrossDomain($app));  // 1.4
}
if($settings['debug']) {
    $app->add(new \App\Middlewares\DevMerge($app));  // 1.1
}