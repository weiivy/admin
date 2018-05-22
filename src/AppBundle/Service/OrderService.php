<?php

namespace AppBundle\Service;

use AppBundle\Entity\Order;
use AppBundle\Entity\OrderPhoto;
use Rain\DB;
use Rain\Log;
use Rain\Pagination;

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

        if ($order->status == Order::STATUS_20) {
            $data['status'] = Order::STATUS_30;
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

}