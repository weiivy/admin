<?php
/**
 * Created by PhpStorm.
 * User: zhangweiwei
 * Date: 18/7/28
 * Time: 下午2:09
 */

namespace Common\Entity;


use Rain\Object;

class BankGoods extends Object
{
    const STATUS_NORMAL = 10; //正常
    const STATUS_DELETE = 20; //删除

    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%bank_goods}}';
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


}