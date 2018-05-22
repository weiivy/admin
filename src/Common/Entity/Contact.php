<?php
namespace Common\Entity;


use Rain\Object;

class Contact extends Object
{
    const SEX_0 = 0; //未知
    const SEX_1 = 1; //男
    const SEX_2 = 2; //女

    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%contact}}';
    }

    /**
     * sex别名
     * @return null|string
     */
    public function sexAlisa()
    {
        $sex = [
            static::SEX_0 => '未知',
            static::SEX_1 => '男',
            static::SEX_2 => '女',
        ];
        return isset($sex[$this->sex]) ? $sex[$this->sex] : null;
    }

    /**
     * 地址
     * @return string
     */
    public function address()
    {
        return $this->country . ' ' . $this->province . ' ' . $this->city;
    }
}