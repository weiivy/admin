<?php

use Rain\Route;

Route::controller('default', 'WeixinBundle\Controller\DefaultController');
Route::controller('product', 'WeixinBundle\Controller\ProductController');
Route::controller('employee', 'WeixinBundle\Controller\EmployeeController');
/*Route::controller('order', 'WeixinBundle\Controller\OrderController');
Route::controller('member', 'WeixinBundle\Controller\MemberController');*/

Route::group(array('before' => 'login'), function(){
    Route::controller('order', 'WeixinBundle\Controller\OrderController');
    Route::controller('member', 'WeixinBundle\Controller\MemberController');
    Route::controller('common', 'WeixinBundle\Controller\CommonController');
});

//注意支付通知回调不要放在login中
Route::controller('wxpay', 'WeixinBundle\\Controller\\WxpayController');

