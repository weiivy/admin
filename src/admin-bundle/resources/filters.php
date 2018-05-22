<?php

use Rain\Auth;
use Rain\Redirect;
use Rain\Route;
use Rain\Session;

//登录过虑器
Route::filter('login', function () {

    if (Auth::isLogin()) {
        return;
    }

    $returnUrl = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    Session::put('returnUrl', $returnUrl);
    return Redirect::to('login');
});
