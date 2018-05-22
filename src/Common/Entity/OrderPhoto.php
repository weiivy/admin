<?php
namespace Common\Entity;


use Rain\Object;

class OrderPhoto extends Object
{
    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return "{{%order_photo}}";
    }
}