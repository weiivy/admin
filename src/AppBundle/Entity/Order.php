<?php

namespace AppBundle\Entity;

use AppBundle\Service\MemberService;
use AppBundle\Service\OrderService;
use Common\Entity\Member;
use Rain\DB;

class Order extends \Common\Entity\Order
{

    /**
     * 下单人
     * @return string
     */
    public function getOrderMember()
    {
        $member = DB::select(Member::tableName())->asEntity(Member::className())->findByPk($this->member_id);
        return $member === null ? '' : $member->nickname;
    }
}