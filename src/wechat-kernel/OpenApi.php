<?php

namespace Rain\Wechat;
use Rain\Cache;
use Rain\Log;
use Rain\Util;

/**
 * 微信第三方平台Api
 * https://open.weixin.qq.com
 *
 *
 * 使用示例
 *
 * 第三方平台信息
 * $component = array(
 *      'appId' => 'wx8a0cccb790f07827',
 *      'appSecret' => '0c79e1fa963cd80cc0be99b20a18faeb',
 *      'token' => 'chehutong2015changyilu188',
 *      'encodingAesKey' => 'qOBzMXQOHcsgKwyN79ocTxqtjaprTTibYb1w4rn3lMy',
 * );
 *
 * 托管后的公众帐号信息
 * $authorize = array(
 *      'appId' => 'wx49acca6ed75c56f8',
 *      'refreshToken' => 'refreshtoken@@@2MKI_ik8BlqOmembQVhYH8IXrDxFOqUf6Pn6PjLYOUc',
 * );
 *
 * $api = new OpenApi($component);
 * $api->setComponentVerifyTicket(@file_get_contents('/tmp/' . $component['appId'] . 'ComponentVerifyTicket'));
 * $api->setAuthorizer($authorize['appId'], $authorize['refreshToken']);
 * $api->getSignPackage();  即可以代替公众号调用api接口
 *
 * @author  Zou Yiliang
 * @since   1.0
 */
class OpenApi extends Api
{
    //授权方公众号appId(代公众号调用接口前，需要设置授权方公众号信息)
    protected $authorizerAppId;
    //授权方公众号refreshToken(代公众号调用接口前，需要设置授权方公众号信息)
    protected $authorizerRefreshToken;

    /**
     * 缓存component_verify_ticket
     * component_verify_ticket由公众平台每隔10分钟，持续推送给第三方平台方（在创建公众号第三方平台审核通过后，才会开始推送）
     * @param $componentVerifyTicket
     */
    public function setComponentVerifyTicket($componentVerifyTicket)
    {
        Cache::put($this->appId . 'ComponentVerifyTicket', $componentVerifyTicket, 60 * 30);
    }

    /**
     * 获取第三方平台令牌（component_access_token）
     * @return string 成功返回component_access_token字符串，失败返回null
     */
    public function componentAccessToken()
    {

        $cacheKey = $this->appId . '.component_access_token';

        $componentAccessToken = Cache::get($cacheKey);
        if ($componentAccessToken != false) {
            return $componentAccessToken;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
        $param = array(
            "component_appid" => $this->appId,
            "component_appsecret" => $this->appSecret,
            "component_verify_ticket" => Cache::get($this->appId . 'ComponentVerifyTicket')
        );

        $jsonStr = Util::curlPost($url, json_encode($param));
        /*
         成功返回如下字符串
          {
              "component_access_token":"61W3mEpU66027wgNZ_MhGHNQDHnFATkDa9-2llqrMBjUwxRSNPbVsMmyD-yq8wZETSoE5NQgecigDrSHkPtIYA",
              "expires_in":7200
          }
        */

        $resultArr = json_decode($jsonStr, true);
        if (is_array($resultArr) && array_key_exists('component_access_token', $resultArr)) {

            $componentAccessToken = $resultArr['component_access_token'];
            $expires = (int)$resultArr['expires_in'];

            //缓存7200秒(2小时)
            if ($expires > 0) {
                Cache::put($cacheKey, $componentAccessToken, $expires);
            }

            return $componentAccessToken;
        }

        Log::error('get component_access_token error: ' . $jsonStr);
        return null;
    }


    /**
     * 获取预授权码 预授权码用于公众号授权时的第三方平台方安全验证
     */
    public function preAuthCode()
    {

        $cacheKey = $this->appId . '.pre_auth_code';

        $preAuthCode = Cache::get($cacheKey);
        if ($preAuthCode != false) {
            return $preAuthCode;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=%s";
        $url = sprintf($url, $this->componentAccessToken());

        $param = array(
            "component_appid" => $this->appId,
        );

        $jsonStr = Util::curlPost($url, json_encode($param));
        /*
         成功返回如下字符串
          {
                "pre_auth_code":"Cx_Dk6qiBE0Dmx4EmlT3oRfArPvwSQ-oa3NL_fwHM7VI08r52wazoZX2Rhpz1dEw",
                "expires_in":1800
          }
        */

        $resultArr = json_decode($jsonStr, true);
        if (is_array($resultArr) && array_key_exists('pre_auth_code', $resultArr)) {

            $preAuthCode = $resultArr['pre_auth_code'];
            $expires = (int)$resultArr['expires_in'];

            //缓存1800秒(30分钟)
            if ($expires > 0) {

                Cache::put($cacheKey, $preAuthCode, $expires);
            }

            return $preAuthCode;
        }

        Log::error('get pre_auth_code error: ' . $jsonStr);
        return null;
    }

    /**
     * 用户点击这个url，将跳转到授权页，同意后跳转回$redirectUri，并附加上如下参数
     * redirect_url?auth_code=xxx&expires_in=3600
     *
     * @param string $redirectUrl
     * @return string
     */
    public function loginPageUrl($redirectUrl)
    {
        $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=%s&pre_auth_code=%s&redirect_uri=%s';
        return sprintf($url, $this->appId, $this->preAuthCode(), urlencode($redirectUrl));
    }

    /**
     * 使用授权码换取公众号的授权信息
     * @param string $authCode 用户点击loginPageUrl成功授权返回时得到auth_code
     * @return array|null 成功返回数组，失败返回null
     *
     * array(
     *     "authorizer_appid"=>"wx00e5904efec77699",
     *     "authorizer_access_token"=>"X8U-qy1EGNqWcQ4K_iR4eNk1nah7CiycHMB9fVShDGLGjAM0SHJivUz0yEQs3K4NFjwZwdPHy3MaCTuUTjN8C4yiePSt-aO0eWk0rWclhqAnBf3rCkB8ADcZRrvyWOud",
     *     "expires_in"=>7200,
     *     "authorizer_refresh_token"=>"Q7Fp7u9KCrNgycLRl8jYOrCuAXo_ztAMYejokp5Hx-4",
     *     "func_info"=> array(
     *         array("funcscope_category"=>array("id"=>1)),
     *         array("funcscope_category"=>array("id"=>2)),
     *         ...
     *     )
     * )
     *
     * 参数说明
     * authorizer_appid    授权方appid
     * authorizer_access_token    授权方令牌（在授权的公众号具备API权限时，才有此返回值）
     * expires_in    有效期（在授权的公众号具备API权限时，才有此返回值）
     * authorizer_refresh_token    刷新令牌（在授权的公众号具备API权限时，才有此返回值），刷新令牌主要用于公众号第三方平台获取和刷新已授权用户的access_token，只会在授权时刻提供，请妥善保存。 一旦丢失，只能让用户重新授权，才能再次拿到新的刷新令牌
     *
     * func_info
     * 众号授权给开发者的权限集列表（请注意，当出现用户已经将消息与菜单权限集授权给了某个第三方，再授权给另一个第三方时，由于该权限集是互斥的，后一个第三方的授权将去除此权限集
     * 1到8分别代表：
     * 消息与菜单权限集
     * 用户管理权限集
     * 帐号管理权限集
     * 网页授权权限集
     * 微信小店权限集
     * 多客服权限集
     * 业务通知权限集
     * 微信卡券权限集
     *
     */
    public function queryAuth($authCode)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=%s';
        $url = sprintf($url, $this->componentAccessToken());

        $param = array(
            'component_appid' => $this->appId,
            'authorization_code' => $authCode,
        );

        $jsonStr = Util::curlPost($url, json_encode($param));

        /* 成功返回如下字符串
         {"authorization_info":{"authorizer_appid":"wx00e5904efec77699","authorizer_access_token":"X8U-qy1EGNqWcQ4K_iR4eNk1nah7CiycHMB9fVShDGLGjAM0SHJivUz0yEQs3K4NFjwZwdPHy3MaCTuUTjN8C4yiePSt-aO0eWk0rWclhqAnBf3rCkB8ADcZRrvyWOud","expires_in":7197,"authorizer_refresh_token":"Q7Fp7u9KCrNgycLRl8jYOrCuAXo_ztAMYejokp5Hx-4","func_info":[{"funcscope_category":{"id":1}},{"funcscope_category":{"id":2}},{"funcscope_category":{"id":3}},{"funcscope_category":{"id":4}},{"funcscope_category":{"id":5}},{"funcscope_category":{"id":6}},{"funcscope_category":{"id":7}},{"funcscope_category":{"id":8}},{"funcscope_category":{"id":11}},{"funcscope_category":{"id":12}},{"funcscope_category":{"id":13}}]}}
        */

        $resultArr = json_decode($jsonStr, true);
        if (is_array($resultArr) && array_key_exists('authorization_info', $resultArr)) {


            $data = $resultArr['authorization_info'];

            $AuthorizerAccessToken = $data['authorizer_access_token'];
            $authorizerAppId = $data['authorizer_appid'];
            $expires = (int)$data['expires_in'];

            //缓存7200秒(120分钟)
            if ($expires > 0) {
                Cache::put($this->getAuthorizerAccessTokenCacheKey($authorizerAppId), $AuthorizerAccessToken, $expires);
            }

            return $data;
        }

        Log::error('api_query_auth error: ' . $jsonStr);
        return null;
    }

    /**
     * 授权方令牌 authorizer_access_token 的缓存key
     * @param $authorizerAppId
     * @return string
     */
    protected function getAuthorizerAccessTokenCacheKey($authorizerAppId)
    {
        return $this->appId . $authorizerAppId . 'AuthorizerAccessToken';
    }

    /**
     * 获取（刷新）授权公众号的令牌
     * 授权方令牌（authorizer_access_token）失效时，用刷新令牌（authorizer_refresh_token）获取新的令牌
     * @param string $authorizerRefreshToken 刷新令牌 在调用queryAuth()时得到
     * @return string
     */
    public function authorizerAccessToken($authorizerAppId, $authorizerRefreshToken, $useCache = true)
    {
        if ($useCache && ($authorizerAccessToken = Cache::get($this->getAuthorizerAccessTokenCacheKey($authorizerAppId))) != false) {
            return $authorizerAccessToken;
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=%s';
        $url = sprintf($url, $this->componentAccessToken());

        $param = array(
            "component_appid" => $this->appId,
            "authorizer_appid" => $authorizerAppId,
            "authorizer_refresh_token" => $authorizerRefreshToken,
        );

        $jsonStr = Util::curlPost($url, json_encode($param));

        /* 成功返回如下字符串
            {
                "authorizer_access_token": "aaUl5s6kAByLwgV0BhXNuIFFUqfrR8vTATsoSHukcIGqJgrc4KmMJ-JlKoC_-NKCLBvuU1cWPv4vDcLN8Z0pn5I45mpATruU0b51hzeT1f8",
                "expires_in": 7200,
                "authorizer_refresh_token": "BstnRqgTJBXb9N2aJq6L5hzfJwP406tpfahQeLNxX0w"
            }
         */
        $resultArr = json_decode($jsonStr, true);

        if (is_array($resultArr) && array_key_exists('authorizer_access_token', $resultArr)) {

            $authorizerAccessToken = $resultArr['authorizer_access_token'];
            $expires = (int)$resultArr['expires_in'];

            //缓存7200秒(120分钟)
            if ($expires > 0) {
                Cache::put($this->getAuthorizerAccessTokenCacheKey($authorizerAppId), $authorizerAccessToken, $expires);
            }

            return $authorizerAccessToken;
        }

        Log::error('authorizerAccessToken error: ' . $jsonStr);
        return null;
    }

    /**
     * 获取授权方的账户信息
     * 获取授权方的公众号基本信息，包括头像、昵称、帐号类型、认证类型、微信号、原始ID和二维码图片URL
     * 需要特别记录授权方的帐号类型，在消息及事件推送时，对于不具备客服接口的公众号，需要在5秒内立即响应；而若有客服接口，则可以选择暂时不响应，而选择后续通过客服接口来发送消息触达粉丝。
     * @param $authorizerAppId
     * @return array|null
     *
     *[
     * authorizer_info": [
     *      "nick_name": "授权方公众号昵称",
     *      "head_img": "授权方公众号头像",
     *      "service_type_info": [ "id": 2 ],   //授权方公众号类型，0代表订阅号，1代表由历史老帐号升级后的订阅号，2代表服务号
     *      "verify_type_info": [ "id": 0 ],    //授权方认证类型，-1代表未认证，0代表微信认证，1代表新浪微博认证，2代表腾讯微博认证，3代表已资质认证通过但还未通过名称认证，4代表已资质认证通过、还未通过名称认证，但通过了新浪微博认证，5代表已资质认证通过、还未通过名称认证，但通过了腾讯微博认证
     *      "user_name":"gh_f4801a82ed3c",      //授权方公众号的原始ID
     *      "alias":"test",                     //授权方公众号所设置的微信号，可能为空
     *      "qrcode_url":"URL",                 //二维码图片的URL，开发者最好自行也进行保存
     *  ],
     *
     * "authorization_info": [
     *      "appid": "wx00e5904efec77699",     //授权方appid
     *      "func_info": [                     //公众号授权给开发者的权限集列表  请注意，当出现用户已经将消息与菜单权限集授权给了某个第三方，再授权给另一个第三方时，由于该权限集是互斥的，后一个第三方的授权将去除此权限集，开发者可以在返回的func_info信息中验证这一点，避免信息遗漏）
     *           [ "funcscope_category": [ "id": 1 ]],
     *           [ "funcscope_category": [ "id": 2 ]],
     *           [ "funcscope_category": [ "id": 3 ]]
     *       ]
     *   ]
     *]
     *
     * 公众号授权给开发者的权限集列表
     * 1到9分别代表：
     * 消息与菜单权限集
     * 用户管理权限集
     * 帐号管理权限集
     * 网页授权权限集
     * 微信小店权限集
     * 多客服权限集
     * 业务通知权限集
     * 微信卡券权限集
     * 微信扫一扫权限集
     */
    public function authorizerInfo($authorizerAppId)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=%s';
        $url = sprintf($url, $this->componentAccessToken());

        $param = array(
            "component_appid" => $this->appId,
            "authorizer_appid" => $authorizerAppId,
        );

        $jsonStr = Util::curlPost($url, json_encode($param));

        /*
        成功返回如下字符串
        {
            "authorizer_info": {
                "nick_name": "昵称",
                "head_img": "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
                "service_type_info": { "id": 2 },
                "verify_type_info": { "id": 0 },
                "user_name":"gh_f4801a82ed3c",
                "alias":"paytest01",
                "qrcode_url":"URL",
            },

             "authorization_info": {
                "appid": "wx00e5904efec77699",
                "func_info": [
                    { "funcscope_category": { "id": 1 } },
                    { "funcscope_category": { "id": 2 } },
                    { "funcscope_category": { "id": 3 } }
                ]
                }
            }
         */
        $resultArr = json_decode($jsonStr, true);

        if (is_array($resultArr) && array_key_exists('authorizer_info', $resultArr)) {
            //Log::warning($resultArr);
            return $resultArr;
        }

        Log::error('authorizerInfo error: ' . $jsonStr);
        return null;
    }

    //获取授权方的选项设置信息
    //获取授权方的公众号的选项设置信息，如：地理位置上报，语音识别开关，多客服开关。注意，获取各项选项设置信息，需要有授权方的授权
    //location_report(地理位置上报选项) 	0无上报  1 进入会话时上报 2 每5s上报
    //voice_recognize（语音识别开关选项） 	0关闭语音识别 1 开启语音识别
    //customer_service（客服开关选项） 	0关闭多客服 1开启多客服
    public function getAuthorizerOption($authorizerAppId, $optionName)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option?component_access_token=%s';
        $url = sprintf($url, $this->componentAccessToken());

        $param = array(
            "component_appid" => $this->appId,
            "authorizer_appid" => $authorizerAppId,
            "option_name" => $optionName,
        );

        $jsonStr = Util::curlPost($url, json_encode($param));

        /*
        成功返回如下字符串
        {
        "authorizer_appid":"wx00e5904efec77699",
        "option_name":"voice_recognize",
        "option_value":"1"
        }
         */

        $resultArr = json_decode($jsonStr, true);

        if (is_array($resultArr) && array_key_exists('option_value', $resultArr)) {
            return $resultArr['option_value'];
        }

        Log::error('getAuthorizerOption error: ' . $jsonStr);
        return null;
    }

    /**
     * 设置授权方的选项信息
     * 设置授权方的公众号的选项信息，如：地理位置上报，语音识别开关，多客服开关。注意，设置各项选项设置信息，需要有授权方的授权，详见权限集说明。
     * @param $authorizerAppId
     * @param $optionName
     * @param $optionValue
     * @return bool
     */
    public function setAuthorizerOption($authorizerAppId, $optionName, $optionValue)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option?component_access_token=%s';
        $url = sprintf($url, $this->componentAccessToken());

        $param = array(
            "component_appid" => $this->appId,
            "authorizer_appid" => $authorizerAppId,
            "option_name" => $optionName,
            "option_value" => (string)$optionValue,
        );

        $jsonStr = Util::curlPost($url, json_encode($param));

        /*
        成功返回如下字符串
        {
            "errcode":0,
            "errmsg":"ok"
        }
         */
        $resultArr = json_decode($jsonStr, true);

        if (is_array($resultArr) && array_key_exists('errcode', $resultArr)) {
            return $resultArr['errcode'] == 0;
        }

        Log::error('setAuthorizerOption error: ' . $jsonStr);
        return false;
    }

    /**
     * 代公众号调用接口前，需要设置授权方公众号信息
     * @param $authorizerAppId
     * @param $authorizerRefreshToken
     */
    public function setAuthorizer($authorizerAppId, $authorizerRefreshToken)
    {
        $this->authorizerAppId = $authorizerAppId;
        $this->authorizerRefreshToken = $authorizerRefreshToken;
    }

    /**
     * 第三方平台方在获得公众号授权后，可以使用authorizer_access_token（即授权公众号的令牌）作为凭证，代替公众号调用API，
     * 调用方式和公众号使用自身API的方式一样（只是需将调用API时提供的公众号自身access_token参数，替换为authorizer_access_token）
     * @param bool $useCache
     * @return string
     */
    public function getAccessToken($useCache = true)
    {
        return $this->authorizerAccessToken($this->authorizerAppId, $this->authorizerRefreshToken, $useCache);
    }

    /**
     * 网页授权获取用户基本信息 流程第1步
     * 引导用户进入授权页面的Url (用户允许后，获取code)
     * @param $redirect_uri 授权后重定向的回调链接地址
     * @param string $state 重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值
     * @param string $scope 应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo（弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
     * @return string
     */
    public function getOauthAuthorizeUrl($redirect_uri, $state = '0', $scope = 'snsapi_userinfo')
    {

        //使用urlencode对链接进行处理
        $redirect_uri = urlencode($redirect_uri);

        //返回类型，请填写code
        $response_type = 'code';

        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->authorizerAppId}&redirect_uri={$redirect_uri}&response_type={$response_type}&scope={$scope}&state={$state}&component_appid={$this->appId}#wechat_redirect";
        return $url;
    }

    /**
     * 网页授权获取用户基本信息 流程第2步
     * 通过code换取网页授权access_token
     * @param $code
     * @return string | null
     */
    public function getOauthAccessToken($code)
    {
        $grant_type = 'authorization_code';
        $component_access_token = $this->componentAccessToken();

        //$url = "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=APPID&code=CODE&grant_type=authorization_code&component_appid=COMPONENT_APPID&component_access_token=COMPONENT_ACCESS_TOKEN";
        $url = "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid={$this->authorizerAppId}&code={$code}&grant_type={$grant_type}&component_appid={$this->appId}&component_access_token={$component_access_token}";

        $output = $this->curlGet($url);

        /*{
           "access_token":"ACCESS_TOKEN",
           "expires_in":7200,					 	access_token接口调用凭证超时时间，单位（秒）
           "refresh_token":"REFRESH_TOKEN",
           "openid":"OPENID",
           "scope":"SCOPE"
        }*/


        //{"errcode":-1,"errmsg":"system error"}

        $arr = json_decode($output, true);
        if (array_key_exists('access_token', $arr)) {
            return $arr;
        }
        Log::error($output);
        return null;
    }

    /**
     * 返回js sdk SignPackage
     *
     * [php] $signPackage = $api->getSignPackage(); [/php]
     *
     * <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
     * <script>
     * wx.config({
     *     appId: '<?php echo $signPackage["appId"];?>',
     *     timestamp: <?php echo $signPackage["timestamp"];?>,
     *     nonceStr: '<?php echo $signPackage["nonceStr"];?>',
     *     signature: '<?php echo $signPackage["signature"];?>',
     *     jsApiList: [
     *         // 所有要调用的 API 都要加到这个列表中
     *         'onMenuShareAppMessage',
     *         'onMenuShareTimeline',
     *         'onMenuShareQQ',
     *         'onMenuShareWeibo',
     *
     *         //拍照或相册
     *         'chooseImage',
     *         //上传图片
     *         'uploadImage'
     *     ]
     * });
     * wx.ready(function () {
     *     // 在这里调用 API
     * });
     * </script>
     *
     * @return array
     */
    public function getSignPackage()
    {
        $jsapiTicket = $this->getJsApiTicket();

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->authorizerAppId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * 公众号用于调用微信JS接口的临时票据
     *
     * http://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html#.E9.99.84.E5.BD.951-JS-SDK.E4.BD.BF.E7.94.A8.E6.9D.83.E9.99.90.E7.AD.BE.E5.90.8D.E7.AE.97.E6.B3.95
     * jsapi_ticket 的type为jsapi (腾讯demo中的JSSDK.php代码中type为1 不知为何)
     * 卡券 api_ticket 的type为 wx_card
     *
     * @param string $type
     * @return string
     */
    public function getJsApiTicket($type = 'jsapi')
    {

        $cacheKey = $this->appId . $this->authorizerAppId . $type . 'jsapi_ticket';

        $ticket = Cache::get($cacheKey);

        if ($ticket !== false) {
            return $ticket;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=%s&access_token=%s";
        $data = json_decode($this->curlGet(sprintf($url, $type, $this->getAccessToken())), true);

        if (is_array($data) && array_key_exists('errcode', $data) && $data['errcode'] == '40001') {

            //access_token无效，尝试跳过缓存从新获取access_token
            Log::warning('access_token cache error');
            $data = json_decode($this->curlGet(sprintf($url, $type, $this->getAccessToken(false))), true);
        }

        if (is_array($data) && array_key_exists('ticket', $data)) {

            $ticket = $data['ticket'];
            Cache::put($cacheKey, $ticket, $data['expires_in']);

            return $ticket;
        }

        Log::error($data);
        return null;
    }

}
