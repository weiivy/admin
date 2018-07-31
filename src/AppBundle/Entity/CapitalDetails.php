<?php
/**
 * Created by PhpStorm.
 * User: zhangweiwei
 * Date: 18/5/23
 * Time: 下午3:15
 */

namespace AppBundle\Entity;


use Rain\DB;

class CapitalDetails extends \Common\Entity\CapitalDetails
{
    /**
     * 来源
     * @return array|null
     */
    public function getFromInfo()
    {
        if(!$this->from_id) return null;
        $member = DB::select(Member::tableName())->asEntity(Member::className())->findByPk($this->from_id);
        return $member;
    }
}