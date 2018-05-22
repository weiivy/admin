<?php

use Rain\Auth;
use Rain\Redirect;
use Rain\Route;
use Rain\Session;

//登录过虑器
Route::filter('login', function () {

    //检测登录是否过期
    if(isset($_COOKIE['auth_key'])) {

         //查询该auth_key对应的用户
        $member = \Rain\DB::select(\Common\Entity\Member::tableName())
            ->asEntity(\Common\Entity\Member::className())->find('auth_key = ?', [$_COOKIE['auth_key']]);

       //用户存在登录
        if($member != null) {
            Auth::login($member);

            //在有效期小于等于一天时，重置有效期
            if($_COOKIE['var_expire'] - time() <= 24*3600) {
                $authKey = uniqid();
                $data['auth_key'] = $authKey;
                $data['updated_at'] = time();
                if(!(\Rain\DB::update(\Common\Entity\Member::tableName(), $data, 'id = ?', [$member->id]) == 1)){
                    \Rain\Log::error("登录修改auth_key失败\n". \Rain\DB::getLastSql());
                } else {
                    $expire = time() + 24*30*3600; // 设置一个月有效期
                    setcookie ("auth_key", $authKey, $expire, '/'); // 设置一个名字为auth_key的cookie，并制定了有效期
                    setcookie ("var_expire", $expire, $expire, '/'); // 再将过期时间设置进cookie以便你能够知道auth_key的过期时间
                }
            }
        }
    }

    //未登录
    if(!Auth::isLogin()) {
        $returnUrl = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http') . '://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        Session::put('returnUrl', $returnUrl);
        if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){

           //ajax 请求的处理方式, 清楚returnUrl
            Session::pull('returnUrl');
            return json_encode(['status' => 0, 'message' => "请去登录"]);
        }else{

            //正常请求的处理方式
            return Redirect::to('default/login');
        };

    }

});