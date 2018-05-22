<?php

namespace Rain;

/**
 * Session类
 */
class Session
{
    protected static $prefix = '';

    protected static function start($sessionId = null)
    {
        if (isset(Application::$app['id'])) {
            static::$prefix = Application::$app['id'] . '.';
        }

        if (!isset($_SESSION)) {

            if ($sessionId !== null) {
                session_id($sessionId);
            }
            session_start();
        }

        if ($sessionId !== null && session_id() !== $sessionId) {
            session_destroy();
            static::start($sessionId);
        }

    }

    /**
     * 储存数据到 Session 中
     * @param $key
     * @param $value
     */
    public static function put($key, $value)
    {
        static::start();
        $key = static::$prefix . $key;
        $_SESSION[$key] = $value;
    }

    /**
     * 从 Session 取回数据
     * @param $key
     * @param null $defaultValue
     * @return
     */
    public static function get($key, $defaultValue = null)
    {
        static::start();
        $key = static::$prefix . $key;
        if (array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }
        return $defaultValue;
    }

    /**
     * 从 Session 取回数据，并删除
     * @param $key
     * @param null $defaultValue
     * @return
     */
    public static function pull($key, $defaultValue = null)
    {
        static::start();
        $key = static::$prefix . $key;
        if (array_key_exists($key, $_SESSION)) {
            $data = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $data;
        }
        return $defaultValue;
    }

    /**
     * 移除 Session 中指定的数据
     * @param $key
     */
    public static function forget($key)
    {
        static::start();
        $key = static::$prefix . $key;
        if (array_key_exists($key, $_SESSION)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * 清空整个 Session
     */
    public static function flush()
    {
        static::start();

        /*
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        */

        foreach ($_SESSION as $key => $value) {
            if (strpos($key, static::$prefix) === 0) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * 放入快闪数据（Flash Data）
     * @param $key
     * @param $value
     */
    public static function setFlash($key, $value)
    {
        static::start();
        $key = static::$prefix . 'flash.' . $key;
        $_SESSION[$key] = $value;
    }

    /**
     * 是否存在快闪数据
     * @param $key
     * @return bool
     */
    public static function hasFlash($key)
    {
        static::start();
        $key = static::$prefix . 'flash.' . $key;
        return isset($_SESSION[$key]);
    }

    /**
     * 返回快闪数据 (成功获取后，将删除该数据)
     * @param $key
     * @param null $defaultValue
     * @return
     */
    public static function getFlash($key, $defaultValue = null)
    {
        static::start();
        $key = static::$prefix . 'flash.' . $key;
        if (array_key_exists($key, $_SESSION)) {

            $data = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $data;
        }
        return $defaultValue;
    }

    public static function token()
    {
        static::start();
        $key = static::$prefix . 'token';
        return static::pull($key);
    }

    public static function regenerateToken()
    {
        static::start();
        $key = static::$prefix . 'token';

        $token = Util::random(40);
        static::put($key, $token);
        return $token;
    }
}