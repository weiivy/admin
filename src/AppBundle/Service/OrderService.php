<?php

namespace AppBundle\Service;

use AppBundle\Entity\BankConfig;
use AppBundle\Entity\CapitalDetails;
use AppBundle\Entity\Member;
use AppBundle\Entity\Order;
use AppBundle\Entity\OrderPhoto;
use Rain\DB;
use Rain\Log;
use Rain\Pagination;
use Rain\Validator;

/**
 * 订单管理服务类
 *
 * @author Zhang Weiwei
 * @since 1.0
 */
class OrderService
{
    /**
     * 订单列表（带分页）
     * @param Pagination $page
     * @param string $condition
     * @param array $params
     * @param string $order
     * @return array
     */
    public static function findOrderList(Pagination $page, $condition = '', $params = [], $order = 'id desc')
    {
        $query = DB::select(Order::tableName())->where($condition, $params);
        $queryCount = clone $query;
        $page->itemCount = $queryCount->count();

        return $query->asEntity(Order::className())
            ->limit($page->limit)
            ->orderBy($order)
            ->findAll();
    }

    /**
     * 根据id返回订单模型
     * @param $id
     * @return array|null
     */
    public static function findOrder($id)
    {
        return DB::select(Order::tableName())->asEntity(Order::className())->findByPk($id);
    }

    /**
     * 订单列表页 => 将订单状态改为审核中
     * @param $id
     * @param array $errors
     * @return bool
     */
    public static function updateOrderWidthReviewing($id, &$errors = [])
    {
        $sql = 'SELECT * FROM ' . Order::tableName() . ' WHERE id =? FOR UPDATE';
        $order = DB::select()->asEntity(Order::className())->findBySql($sql, [$id]);

        if ($order === null) {
            $errors[] = "订单ID错误";
            return false;
        }

        if ($order->status == Order::STATUS_10) {
            $data['status'] = Order::STATUS_20;
            $data['updated_at'] = time();
            if (DB::update(Order::tableName(), $data, 'id = ?', [$id]) == 1) {
                return true;
            } else {
                $errors[] = "服务器错误";
                Log::error("订单确认修改失败\n" . DB::getLastSql());
            }
        }

        $errors[] = "服务器错误";
        return false;
    }

    /**
     * 订单列表页 => 将订单状态改为审核成功
     * @param $id
     * @param array $errors
     * @return bool
     */
    public static function updateOrderWithSuccess($id, &$errors = [])
    {
        $sql = 'SELECT * FROM ' . Order::tableName() . ' WHERE id =? FOR UPDATE';
        $order = DB::select()->asEntity(Order::className())->findBySql($sql, [$id]);

        if ($order === null) {
            $errors[] = "订单ID错误";
            return false;
        }

        $member = MemberService::findMember($order->member_id);
        if(!$member) {
            $errors[] = "会员不存在";
            return false;
        }

        //获取银行配置点数
        $bankConfig = DB::select(BankConfig::tableName())
            ->asEntity(BankConfig::className())
            ->find('bank_id=:bank and type=:type', [':bank'=> $order->bank_id, ':type' => $member->grade]);
        if(empty($bankConfig)){
            $errors[] = "银行未配置兑换比例";
            return false;
        }
        $money = round((($bankConfig->money / $bankConfig->score) * $order->integral), 2);
        DB::getConnection()->beginTransaction();
        if ($order->status == Order::STATUS_20) {
            $data['status'] = Order::STATUS_30;
            $data['money'] = $money;
            $data['updated_at'] = time();
            if (DB::update(Order::tableName(), $data, 'id = ?', [$id]) == 1) {

                //添加交易明细
                $capitalDetails = [
                    'member_id' => $order->member_id,
                    'type' => '+',
                    'status' => CapitalDetails::STATUS_1,
                    'kind' => CapitalDetails::KIND_50,
                    'money' => $money,
                ];
                if(!static::addCapitalDetails($capitalDetails)) {
                    DB::getConnection()->rollBack();
                    return false;
                }
                if(Member::editMoney($order->member_id, $money) && static::commission($id, $member, $money)) {
                    DB::getConnection()->commit();
                    return true;
                }

            }
        }
        Log::error("订单确认修改失败\n" . DB::getLastSql());
        $errors[] = "服务器错误";
        DB::getConnection()->rollBack();
        return false;
    }

    /**
     * 订单列表页 => 将订单状态改为审核失败
     * @param $id
     * @param array $errors
     * @return bool
     */
    public static function updateOrderWithFail($id, &$errors = [])
    {
        $sql = 'SELECT * FROM ' . Order::tableName() . ' WHERE id =? FOR UPDATE';
        $order = DB::select()->asEntity(Order::className())->findBySql($sql, [$id]);

        if ($order === null) {
            $errors[] = "订单ID错误";
            return false;
        }

        if ($order->status == Order::STATUS_20) {
            $data['status'] = Order::STATUS_40;
            $data['updated_at'] = time();
            if (DB::update(Order::tableName(), $data, 'id = ?', [$id]) == 1) {
                return true;
            } else {
                $errors[] = "服务器错误";
                Log::error("订单确认修改失败\n" . DB::getLastSql());
            }
        }

        $errors[] = "服务器错误";
        return false;
    }

    /**
     * 订单图片
     * @param Order $order
     * @return array
     */
    public static function findOrderPhoto(Order $order)
    {
        return DB::select(OrderPhoto::tableName())->where('order_id =:oid', [':oid' =>  $order->id])
            ->asEntity(OrderPhoto::className())->findAll();
    }


    /**
     * 提成
     * @author Ivy Zhang<ivyzhang@lulutrip.com>
     * @copyright 2018-05-04
     * @param $orderId
     * @param $money
     * @return bool
     */
    public static function commission($orderId, Member $member, $money)
    {
        //检查订单是否存在
        $order = DB::select(Order::tableName())->asEntity(Order::className())->findByPk($orderId);

        if(empty($order)) return true;

        //检查该用户是否有父级
        $pid = MemberService::getPid($order->member_id);
        if( $pid === 0) {
            return true;
        }

        $topMember = MemberService::findMember($pid);
        if($member->grade >= $topMember->grade) {
            return true;
        }

        //获取银行配置点数
        $bankConfig = DB::select(BankConfig::tableName())
            ->asEntity(BankConfig::className())
            ->find('bank_id=:bank and type=:type', [':bank'=> $order->bank_id, ':type' => $topMember->grade]);
        if(empty($bankConfig)) return true;

        DB::getConnection()->beginTransaction();
        //有父级给父级提成
        $commission =  round(($bankConfig->money - $money), 2);


        //新增
        $capitalDetails = [
            'member_id' => $pid,
            'type' => '+',
            'status' => CapitalDetails::STATUS_1,
            'kind' => CapitalDetails::KIND_20,
            'money' => $commission,
        ];
        if(!static::addCapitalDetails($capitalDetails)) {
            DB::getConnection()->rollBack();
            return false;
        }

        //修改pid用户金额
        if(!Member::editMoney($pid, $commission)) {
            Log::error(DB::getLastSql());
            DB::getConnection()->rollBack();
            return false;
        }


        DB::getConnection()->commit();
        return true;
    }

    /**
     * 给上级提出
     * @param $data
     * @return bool
     */
    public static function addCapitalDetails($data)
    {
        $rule = [
            [['member_id', 'kind','money', 'type', 'status'], 'required'],
            [['member_id', 'kind', 'status'], 'integer'],
            [['money'], 'double'],
            [['type'], 'string'],
        ];

        if(!Validator::validate($data, $rule)) {

            $errors = Validator::getFirstErrors();
            Log::error(json_encode($errors));
            return false;
        }

        $data['created_at'] = $data['updated_at'] = time();
        if(DB::insert(CapitalDetails::tableName(), $data)) {
            return true;
        } else {
            Log::error("提出失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }


    /**
     * 修改订单金额
     * @param Order $order
     * @param $money
     * @return bool
     */
    public static function UpdateOrderMoney(Order $order, $money)
    {
        $data['money'] = $money;
        $data['updated_at'] = time();
        if(DB::update(Order::tableName(), $data, 'id = ?', [$order->id]) == 1) {
            return true;
        } else {
            Log::error("提出失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

}