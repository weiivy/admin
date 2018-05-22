<?php

namespace Rain\Facade;
use Rain\Application;

/**
 * 数据库操作帮助类
 *
 * @author  Zou Yiliang
 */
class DBFacade
{
    public static function  __callStatic($name, $arguments)
    {
        $dbHelp = Application::$app['db'];
        if (method_exists($dbHelp, $name)) {
            return call_user_func_array([$dbHelp, $name], $arguments);
        }

        $db = $dbHelp->getConnection();
        if (method_exists($db, $name)) {
            return call_user_func_array([$db, $name], $arguments);
        }
        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }
}