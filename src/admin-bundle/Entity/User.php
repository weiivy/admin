<?php

namespace Rain\Bundle\AdminBundle\Entity;


class User extends \Rain\Auth\User
{
    //允许登录
    const  STATUS_ALLOW = 10;
    //禁止登录
    const  STATUS_DENY = 20;
    //标记为已删除
    const  STATUS_DELETE = 30;


}