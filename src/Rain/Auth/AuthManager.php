<?php

namespace Rain\Auth;

use Rain\Application;
use Rain\Session;

/**
 * 用户认证帮助类
 * @author  Zou Yiliang
 */
class AuthManager
{
    protected static function getSessionKeyWithPrefix($key = '')
    {
        return 'auth.id.' . md5((isset(Application::$app['id']) ? Application::$app['id'] : 'rain') . $key);
    }

    /**
     * 将指定用户置为登录状态
     * @param IdentityInterface $identity 已通过验证允许登录的用户对象
     * @return bool
     */
    public static function login(IdentityInterface $identity)
    {
        $id = $identity->getId();
        Session::put(static::getSessionKeyWithPrefix(), $id);
        return true;
    }

    /**
     * 注销
     * @return bool
     */
    public static function logout()
    {
        Session::forget(static::getSessionKeyWithPrefix());
        return true;
    }

    /**
     * 返回当前登录用户的id
     * @return string|int|null
     */
    public static function getId()
    {
        return Session::get(static::getSessionKeyWithPrefix(), null);
    }

    /**
     * 是否已登录
     * @return bool
     */
    public static function isLogin()
    {
        return null !== static::getId();
    }

    /**
     * 返回当前已登录用户
     * @param string $className 实现了IdentityInterface接口的类名，例如 User::className()
     * @return IdentityInterface|null
     */
    public static function getIdentity($className = 'Rain\Auth\User')
    {
        $id = Session::get(static::getSessionKeyWithPrefix(), null);
        if (is_null($id)) {
            return null;
        }
        return $className::findIdentity($id);
    }

}