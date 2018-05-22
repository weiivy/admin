<?php
namespace Common\Entity;

use Rain\Object;

/**
 * 后台用户实体
 *
 */
class User extends Object
{

    const  STATUS_ALLOW = 10;     //允许登录
    const  STATUS_DENY = 20;      //禁止登录
    const  STATUS_DELETE = 30;    //标记为已删除

    public static function tableName()
    {
        return "{{%user}}";
    }


}