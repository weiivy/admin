<?php

namespace Rain\Bundle\AdminBundle\Controller;

use Rain\Application;
use Rain\Auth;
use Rain\Bundle\AdminBundle\Service\UserService;
use Rain\DB;
use Rain\Redirect;
use Rain\Request;
use Rain\Session;
use Rain\Validator;
use Rain\View;
use Rain\Mail;
use Rain\Url;

/**
 *
 * @author  Zou Yiliang
 * @since   1.0
 */
class DefaultController
{

    public function index()
    {
        return View::render('@AdminBundle/default/index.twig');
    }

    //登录
    public function login(Request $request)
    {
        //get
        if ($request->isMethod('get')) {
            return View::render('@AdminBundle/default/login.twig');
        }

        //ajax post
        if (UserService::loginByNameAndPassword($request->get('username'), $request->get('password'), $errorMessage)) {
            $returnUrl = isset($_GET['returnUrl']) ? $_GET['returnUrl'] : null;
            if ($returnUrl === null) {
                $returnUrl = Session::pull('returnUrl', Url::to('/'));
            }

            $result['status'] = 1;
            $result['returnUrl'] = $returnUrl;
        } else {
            $result['status'] = 0;
            $result['message'] = $errorMessage;
        }
        return json_encode($result);
    }

    /**
     * 注册
     */
    public function register(Request $request)
    {
        //get
        if ($request->isMethod('get')) {
            return View::render('@AdminBundle/default/register.twig');
        }

        //ajax post
        $data['username'] = $request->get('email');
        $data['email'] = $data['username'];
        $data['password'] = $request->get('password');

        if (!UserService::register($data, $message)) {
            return json_encode([
                'status' => 0,
                'message' => $message,
            ]);
        }
        return json_encode([
            'status' => 1,
            'message' => 'SUCCESS',
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return Redirect::to('/');
    }

    /**
     * 找回密码
     * @author Zhang Weiwei
     */
    public function forgot(Request $request)
    {
        $result = [
            'status' => 0,
            'message' => ''
        ];

        if ($request->isMethod('post')) {

            //验证用户是否存在
            $name = $request->get('username');
            $seller = DB::select(Seller::$table)->asEntity(Seller::className())->find('username=?', [$name]);

            if ($seller == null) {
                $result['message'] = "没有找到匹配用户";
                return json_encode($result);
            }

            //验证户邮箱是否存在
            if (empty($seller->email)) {
                $result['message'] = "该用户暂无邮箱";
                return json_encode($result);
            }

            // 重置password_reset_token，并发邮件
            if ($token = SellerService::changePasswordResetToken($seller)) {

                if ($token != false) {
                    Mail::$config = Application::$app['params']['mail'];

                    //将重置密码连接分配到模板
                    $template = '亲爱的，您好：<br />请您点击下面链接来修复您的登录密码:<br />
                        <a href="{{ url }}">{{ url }}</a><br />
                        为了确保您的帐号安全，该链接仅24小时内访问有效。<br />如果点击链接不能正确跳转...<br />
                        请您选择并复制整个链接，打开浏览器窗口并将其粘贴到地址栏中。然后单击"转到"按钮或按键盘上的 Enter 键。<br />
                        请勿直接回复此邮件。';
                    $template = View::renderText($template, ['url' => Url::to('password-reset', ['token' => $token], true)]);

                    //发送邮件
                    $bool = Mail::send($seller->email, '重置密码邮件', $template);

                    if ($bool) {

                        //重新组装邮箱，例如 2992******@qq.com
                        $temp = str_split($seller->email);
                        $emailShort = '';
                        for ($i = 0; $i < count($temp); $i++) {
                            if ($i < 4) {
                                $emailShort .= $temp[$i];
                            } else if ($i >= strpos($seller->email, '@')) {
                                $emailShort .= $temp[$i];
                            } else {
                                $emailShort .= "*";
                            }
                        }

                        /*$reg = "/(.{3}).+(@.+)/";
                        $emailShort = preg_replace($reg, "$1******$2",$seller->email);*/

                        $result['message'] = '一封邮件发送到了<span>' . $emailShort . '</span>';
                        $result['status'] = 1;
                    } else {
                        $result['message'] = '邮件发送失败';
                    }
                    return json_encode($result);
                }
            }

        }
        return View::render('@AdminBundle/default/forgot.twig');
    }

    /**
     * 重置密码
     * @author Zhang Weiwei
     */
    public function passwordReset(Request $request)
    {
        $result = [
            'status' => 0,
            'message' => ''
        ];

        $errors = [];

        if ($request->isMethod('post')) {
            $name = $request->get('username');
            $token = $request->get('token');
            $data['password'] = $request->get('password');

            $seller = DB::select(Seller::$table)->asEntity(Seller::className())->find('username=?', [$name]);
            if ($seller == null) {
                $result['message'] = "没有找到匹配用户";
            }

            //验证token
            if (SellerService::validatePasswordResetToken($token, $seller)) {

                //新密码和原密码不能一致
                if ($seller->password_hash == Seller::makePasswordHash($data['password'])) {
                    $result['message'] = "新密码和原密码不能一致";
                    return json_encode($result);
                }

                //修改密码
                if (SellerService::updatePassword($data, $seller, $errors)) {
                    $result['message'] = '修改密码成功';
                    $result['status'] = 1;
                } else {
                    $result['message'] = "修改密码失败";
                }

            } else {
                $result['message'] = "修改密码链接已失效";
            }

            return json_encode($result);
        }

        return View::render('@AdminBundle/default/reset.twig');
    }
}
