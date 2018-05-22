<?php

namespace Common\Entity;

use Rain\Object;

/**
 * 订单实体
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class Order extends Object
{
    const STATUS_10 = 10;  // 待审核
    const STATUS_20 = 20;  // 审核中
    const STATUS_30 = 30;  // 审核成功
    const STATUS_40 = 40;  // 审核失败


    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return "{{%order}}";
    }

    /**
     * 订单状态
     * @author Ivy Zhang<ivyzhang@lulutrip.com>
     * @copyright 2018-04-10
     * @param $kind
     * @return mixed|null
     */
    public  function statusAlisa()
    {
        $array = [
            static::STATUS_10 => '待审核 ',
            static::STATUS_20 => '审核中',
            static::STATUS_30 => '审核成功',
            static::STATUS_40 => '审核失败'
        ];
        return isset($array[$this->status]) ? $array[$this->status] : null;
    }


}