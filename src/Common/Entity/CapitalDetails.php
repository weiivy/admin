<?php
namespace Common\Entity;


use Rain\Object;

class CapitalDetails extends Object
{

    const STATUS_1 = 1;  //正常
    const STATUS_2 = 2;  //待处理

    //交易类别：10 充值、20 提成、30 升级返现、40 提现
    const KIND_10 = 10; //充值
    const KIND_20 = 20; //提成
    const KIND_30 = 30; //升级返现
    const KIND_31 = 31; //升级
    const KIND_40 = 40; //提现
    const KIND_50 = 50; //兑换

    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return "{{%capital_details}}";
    }


    /**
     * 交易类别别名
     * @return mixed|null
     */
    public  function kindAlisa()
    {
        $array = [
            static::KIND_10 => '充值',
            static::KIND_20 => '提成',
            static::KIND_30 => '升级返现',
            static::KIND_31 => '升级',
            static::KIND_40 => '提现',
            static::KIND_50 => '兑换',
        ];
        return isset($array[$this->kind]) ? $array[$this->kind] : null;
    }

    /**
     * 状态别名
     * @return mixed|null
     */
    public  function statusAlisa()
    {
        $array = [
            static::STATUS_1 => '正常',
            static::STATUS_2 => '待处理',
        ];
        return isset($array[$this->status]) ? $array[$this->status] : null;
    }


}