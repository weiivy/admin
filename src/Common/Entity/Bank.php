<?php
namespace Common\Entity;


use Rain\Object;

/**
 * 银行实体
 * @copyright (c) 2018, lulutrip.com
 * @author  Ivy Zhang<ivyzhang@lulutrip.com>
 */
class Bank extends Object
{
    const STATUS_NORMAL = 10; //正常
    const STATUS_DELETE = 20; //删除

    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%bank}}';
    }


    /**
     * 状态参数
     * @return array
     */
    public static function statusParams()
    {
        return array(
            static::STATUS_NORMAL => '正常',
            static::STATUS_DELETE => '删除'
        );
    }


    /**
     * 状态别名
     * @return null|string
     */
    public function statusAlias()
    {
        $status = static::statusParams();
        return isset($status[$this->status]) ? $status[$this->status] : null;
    }



}