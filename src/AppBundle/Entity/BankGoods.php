<?php

namespace AppBundle\Entity;


use Rain\DB;

class BankGoods extends \Common\Entity\BankGoods
{
    /**
     * 银行信息
     * @return array|null
     */
    public function getBanks()
    {
        $res = DB::select(Bank::tableName())->asEntity(Bank::className())->findByPk($this->bank_id);
        return !$res ? [] : $res;
    }
}