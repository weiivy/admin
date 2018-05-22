<?php
$app['params'] = [

    'wxpay' => [
        'mch_id' => '1261797801',
        'appid' => 'wxa18aa6a1e0a22a24',
        'key' => 'b4944959c6eaed3194573a0286e39693',
    ],
    'template_id' => 'mHR7_UAYMx5cTRGoHSzYh7bqe98cjjcDP90F_cNTtSI',

    'weixin' => [
        'appId' => 'wxa18aa6a1e0a22a24',
        'appSecret' => 'e4e94f735843ba2a977c34e46cf53c47',
        'token' => '56042f7e3c087',
        'encodingAesKey' => 'evMmyR1MVzThUND42otejvWhm8IriuPyPDb3D7ZTdye'
    ],

    //'authorizeRedirectUrl' => 'http://mp.izhanlue.com/api/wechat/authorizeRedirect',
    'authorizeRedirectUrl' => null,
];

/*$app['employee'] = [
    'order' => "http://kangjun.chehutong.cn/employee.php/order"
];

$app['member'] = [
    'order' => "http://kangjun.chehutong.cn/weixin.php/order"
];*/

$app['employee'] = [
    'order' => isset($_SERVER['HTTP_HOST']) ? ($_SERVER['HTTP_HOST'] . "/employee.php/order") : null
];

$app['member'] = [
    'order' => isset($_SERVER['HTTP_HOST']) ? ($_SERVER['HTTP_HOST'] . "/weixin.php/member") : null
];

//发手机验证码
//$bool = $app['sms']->templateSMS(18610089516, 13888, '1234');
$app['sms'] = function () {
    $sms = new \SMS\UcpaasSMS();
    $sms->accountSid = '7524752fc686217bbbfb78bf6901c826';
    $sms->token = '4203ea18247cf79a1d06f73ac3ef0e99';
    $sms->appId = "7eded75983414a36811c0e4377f3935c";
    return $sms;
};