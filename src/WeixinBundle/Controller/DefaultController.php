<?php

namespace WeixinBundle\Controller;

use InterfaceBundle\Service\MemberService;
use Rain\Application;
use Rain\Auth;
use Rain\Log;
use Rain\Redirect;
use Rain\Request;
use Rain\View;

/**
 * 默认控制器
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class DefaultController
{
    /**
     * 会员注册
     */
    public function anyRegister(Request $request)
    {
        //get
        if ($request->isMethod('get')) {
            return View::render('@WeixinBundle/default/register.twig');
        }

        //ajax post
        $data['username'] = $request->get('mobile');
        $data['mobile'] = $data['username'];
        $data['code'] = $request->get('code');
        $data['password'] = $request->get('password');

        $result = MemberService::register($data);
        return json_encode($result);
    }

    /**
     * 会员登录
     */
    public function anyLogin(Request $request)
    {
        //get
        if ($request->isMethod('get')) {
            return View::render('@WeixinBundle/default/login.twig');
        }

        //ajax post
        $returnUrl = isset($_GET['returnUrl']) ? $_GET['returnUrl'] : null;
        $result = MemberService::loginByNameAndPassword($request->get('username'), $request->get('password'), $returnUrl);
        return json_encode($result);
    }

    public function anyNote(Request $request)
    {
        //get
        if ($request->isMethod('get')) {
            return View::render('@WeixinBundle/default/note.twig');
        }

        //ajax post
        $returnUrl = isset($_GET['returnUrl']) ? $_GET['returnUrl'] : null;
        $result = MemberService::loginByNote($request->get('username'), $request->get('code'), $returnUrl);
        return json_encode($result);
    }

    /**
     * 退出
     */
    public function getLogout(){
        Auth::logout();
        return Redirect::to('product/index');
    }

    /**
     * 找回密码,发送验证码
     */
    public function anyAjaxSendCode(Request $request, Application $app)
    {
        $username = $request->get('username', null);
        $result = MemberService::forgotPassword($username, $app);
        return json_encode($result);

    }

    /**
     *  注册/短信登录，发送验证码
     */
    public function anyAjaxSendRegister(Request $request, Application $app)
    {
        $username = $request->get('username', null);
        $result = MemberService::sendRegister($username, $app);
        return json_encode($result);
    }


     /**
     * 忘记密码
     */
    public function anyReset(Request $request)
    {
        if($request->isMethod('get')){
            return View::render('@WeixinBundle/default/reset.twig');
        }

        $data['username'] = $request->get('username', null);
        $data['password'] = $request->get('password', null);
        $data['code'] = $request->get('code', null);
        $result = MemberService::passwordReset($data);
        return json_encode($result);

    }

    /**
     * 关于我们
     */
    public function getOur()
    {
         return View::render('@WeixinBundle/member/our.twig');
    }

    /**
     * 免责声明
     */
    public function getAgreement()
    {
         return View::render('@WeixinBundle/default/agreement.twig');
    }

    /**
     * 使用协议
     */
    public function getDuty()
    {
         return View::render('@WeixinBundle/default/duty.twig');
    }



}