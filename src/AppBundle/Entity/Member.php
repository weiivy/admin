<?php

namespace AppBundle\Entity;

use Rain\DB;

class Member extends \Common\Entity\Member
{
    /**
     * 父级信息
     * @return array|null
     */
    public function getPidInfo()
    {
        if(!$this->pid) return null;
        $member = DB::select(Member::tableName())->asEntity(Member::className())->findByPk($this->pid);
        return $member;
    }

}