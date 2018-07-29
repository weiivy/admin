<?php

namespace AppBundle\Service;
use AppBundle\Entity\BankGoods;
use Rain\DB;
use Rain\Log;
use Rain\Pagination;
use Rain\Validator;

/**
 * 银行兑换商品
 * @copyright (c) 2018
 * @author  Weiwei Zhang<zhangweiwei@2345.com>
 */
class BankGoodsService
{
    /**
     * 银行兑换商品列表（带分页）
     * @param Pagination $page
     * @param array $condition
     * @param array $params
     * @param string $order
     * @return array
     */
    public static function findBankGoodsList(Pagination $page, $condition = [], $params = [], $order = 'bank_id desc')
    {
        $condition[] = 'status = :status';
        $params[':status'] = BankGoods::STATUS_NORMAL;
        $query = DB::select(BankGoods::tableName())->where(implode(' and ', $condition), $params);
        $queryCount = clone $query;
        $page->itemCount = $queryCount->count();
        $data = $query->asEntity(BankGoods::className())
            ->limit($page->limit)
            ->orderBy($order)
            ->findAll();
        foreach ($data as $key => $val) {
            $data[$key]['money'] = $data[$key]['money'] ? json_decode($data[$key]['money'], true) : [];
        }
        return $data;
    }

    /**
     * 新增银行兑换商品
     * @param $data
     * @param array $errors
     * @return bool
     */
    public static function addBankGoods($data, &$errors = [])
    {
        $rule = [
            [['bank_id', 'codenum','money', 'goods', 'num'], 'required'],
            [['status', 'codenum', 'bank_id'], 'integer'],
            [['goods', 'num', 'money'], 'string'],
        ];

        if(!Validator::validate($data, $rule)) {
            $errors = Validator::getFirstErrors();
            return false;
        }
        $data['created_at'] = $data['updated_at'] = time();
        if(DB::insert(BankGoods::tableName(), $data)) {
            return true;
        } else {
            Log::error("新增银行兑换商品失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

    /**
     * 根据id返回会员模型
     * @param $id
     * @return array|null
     */
    public static function findBankBankGoods($id)
    {
        return DB::select(BankGoods::tableName())->where('status =:status', [':status' => BankGoods::STATUS_NORMAL])->asEntity(BankGoods::className())->findByPk($id);
    }

    /**
     * 修改银行兑换商品
     * @param $id
     * @param $data
     * @param array $errors
     * @return bool
     */
    public static function updateBankGoods($id, $data, &$errors = [])
    {
        $rule = [
            [['bank_id', 'codenum','money', 'goods', 'num'], 'required'],
            [['status', 'codenum', 'bank_id'], 'integer'],
            [['goods', 'num', 'money'], 'string'],
        ];

        if(!Validator::validate($data, $rule)) {
            $errors = Validator::getFirstErrors();
            return false;
        }

        $data['updated_at'] = time();
        if(DB::update(BankGoods::tableName(), $data, 'id = ?', [$id]) == 1) {
            return true;
        } else {
            Log::error("修改银行兑换商品失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

    /**
     * 软删除银行兑换商品
     * @param BankGoods $bankConfig
     * @param array $errors
     * @return bool
     */
    public static function deleteBankGoods(BankGoods $bankConfig, &$errors = [])
    {
        $data['status'] = BankGoods::STATUS_DELETE;
        $data['updated_at'] = time();
        if(DB::update(BankGoods::tableName(), $data, 'id = ?', [$bankConfig->id])) {
            return true;
        } else {
            Log::error("删除银行兑换商品.\n" . DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }
}