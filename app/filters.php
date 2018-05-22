<?php


use Rain\Route;

/**
 * 对post表单进行跨站攻击保护(CSRF)
 * 在表单内引入 CSRF 标记  <input type="hidden" name="_token" value="{{ csrf_token() }}">
 */
Route::filter('post-csrf', function () {

    $request = \Rain\Application::$app->getRequest();
    if ($request->isMethod('post')) {
        $token = $request->get('_token');
        if (empty($token) || Session::token() !== $token) {
            throw new \Exception('Token mismatch exception');
        }
    }
});

require_once Rain\Application::$app->getBundle('AdminBundle')->getPath() . '/resources/filters.php';
require_once Rain\Application::$app->getBundle('AppBundle')->getPath() . '/resources/filters.php';

//require_once Rain\Application::$app->getBundle('TicketAdminBundle')->getPath() . '/resources/filters.php';
//require_once Rain\Application::$app->getBundle('InterfaceBundle')->getPath() . '/resources/filters.php';