<?php
namespace Common\Entity;


use Rain\Object;

class BankConfig extends Object
{
    const TYPE_10 = 10; //合伙人
    const TYPE_20 = 20; //代理
    const TYPE_30 = 30; //股东

    const STATUS_NORMAL = 10; //正常
    const STATUS_DELETE = 20; //删除

    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%bank_config}}';
    }

    /**
     * 类型参数
     * @return array
     */
    public static function typeParams()
    {
        return [
            static::TYPE_10 => '合伙人',
            static::TYPE_20 => '代理',
            static::TYPE_30 => '股东',
        ];
    }

    /**
     * 类型别名
     * @return mixed|null
     */
    public function typeAlisa()
    {
        $type = static::typeParams();
        return isset($type[$this->type]) ? $type[$this->type] : null;
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