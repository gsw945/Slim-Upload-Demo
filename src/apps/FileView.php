<?php

namespace App\Views;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * 文件视图
 */
class FileView extends \App\Controllers\ControllerBase {
    /**
     * 主页面
     */
    public function single(Request $request, Response $response, $args=[]) {
        $params = [];

        return $this->ci->get('twig')->render($response, 'single-upload.twig', $params);
    }
}