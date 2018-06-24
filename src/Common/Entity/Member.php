<?php

namespace Common\Entity;

use AppBundle\Service\MemberService;
use AppBundle\Service\ProductService;
use Common\Service\ActualPayService;
use Rain\DB;
use Rain\Object;
use Rain\Auth\IdentityInterface;

/**
 * 会员实体
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class Member extends Object implements IdentityInterface
{
    const STATUS_ON = 10;  //启用
    const STATUS_OFF = 20; //冻结

    const GRADE_10 = 10;  //会员
    const GRADE_20 = 20;  //代理
    const GRADE_30 = 30;  //股东
    /**
     * 表名
     * @return string
     */
    public static function tableName()
    {
        return "{{%member}}";
    }

    /**
     * 会员等级
     * @return array
     */
    public static function gradeParams()
    {
        return [
            static::GRADE_10=> "会员",
            static::GRADE_20 => "代理",
            static::GRADE_30 => "股东"
        ];
    }

    /**
     * 会员等级别名
     * @return null
     */
    public function gradeAlias()
    {
        $grade = static::gradeParams();
        return isset($grade[$this->grade]) ? $grade[$this->grade] : null;

    }


    /**
     * 返回用户ID
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 根据ID返回用户对象
     * @param int|string $id
     * @return array|null|IdentityInterface
     */
    public static function findIdentity($id)
    {
        return DB::select(static::tableName())->asEntity(static::className())->findByPk($id);
    }


    /**
     * 状态参数
     * @return array
     */
    public static function statusParams()
    {
        return [
            static::STATUS_ON => "启用",
            static::STATUS_OFF => "冻结"
        ];
    }

    /**
     * 状态别名
     * @return null
     */
    public function statusAlias()
    {
        $status = static::statusParams();
        return isset($status[$this->status]) ? $status[$this->status] : null;
    }


    /**
     * 更新会员金额
     * @param $memberId
     * @param $money
     * @return bool
     */
    public static function editMoney($memberId, $money)
    {
        $sql = 'UPDATE ' . Member::tableName() . ' SET `money` = `money` + ? WHERE id = ?';
        return DB::getConnection()->execute($sql, array($money, $memberId)) == 1;
    }




}