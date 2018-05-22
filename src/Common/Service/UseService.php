<?php
namespace Common\Service;

use Common\Entity\ActualPay;
use Common\Entity\TicketUse;
use InterfaceBundle\Service\TicketService;
use Rain\DB;
use Rain\Log;
use Rain\Validator;

/**
 * 卡券与实际支付关联服务类
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class UseService
{
    /**
     * 添加卡券使用记录
     * @param $data
     * @return bool
     */
    public static function addTicketUse($data)
    {
        $rule = [
            [['ticket_id', 'actual_pay_id'], 'required'],
            [['ticket_id', 'actual_pay_id'], 'integer']
        ];

        if(!Validator::validate($data, $rule)) {
            Log::error("添加卡券使用记录失败\n". Validator::getFirstError());
            return false;
        }

        $data['created_at'] = time();
        if(DB::insert(TicketUse::tableName(), $data)) {
            return true;
        } else {
            Log::error("添加卡券使用记录失败\n". DB::getLastSql());
            return false;
        }
    }


    /**
     * 删除卡券详情记录
     * @param $actualId
     * @param $memberId
     * @return bool
     */
    public static function deleteTicketUse($actualId, $memberId)
    {
        //修改卡券状态，改为未使用
        $ticketUse = DB::select(TicketUse::tableName())
            ->asEntity(TicketUse::className())
            ->find('actual_pay_id = ?', [$actualId]);

        if(DB::delete(TicketUse::tableName(), 'actual_pay_id = ?', [$actualId])) {
            TicketService::updateTicketStatusForUnUse($ticketUse->ticket_id, $memberId);
            return true;
        } else {
            Log::error("微信支付时, 删除卡券使用详情原纪录失败.\n". DB::getLastSql());
            return false;
        }

    }

    /**
     * 根据id返回模型
     * @param $id
     * @return array|null
     */
    public static function findInfo($id)
    {
        return DB::select(TicketUse::tableName())
            ->asEntity(TicketUse::className())
            ->findByPk($id);
    }
}