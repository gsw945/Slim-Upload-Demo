<?php

namespace App\Views;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * 文件视图
 */
class FileView extends \App\Controllers\ControllerBase {
    /**
     * 单文件上传页面
     */
    public function single(Request $request, Response $response, $args=[]) {
        $params = [];

        return $this->ci->get('twig')->render($response, 'single-upload.twig', $params);
    }

    /**
     * 多文件上传页面
     */
    public function multiple(Request $request, Response $response, $args=[]) {
        $params = [];

        return $this->ci->get('twig')->render($response, 'multiple-upload.twig', $params);
    }
}