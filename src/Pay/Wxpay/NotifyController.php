<?php
namespace Pay\Wxpay;

use Rain\DB;
use Rain\Log;

class NotifyController
{
    //微信支付通知
    //Route::any('pay/notify/wxpay/{wxpayId}', ['as' => 'pay/notify/wxpay', 'm\controllers\WxpayController@notify'])->where(['wxpayId' => '\d+']);
    public function notify($wxpayId)
    {
        $wxpay = DB::select(Wxpay::$table)->asEntity(Wxpay::className())->findByPk($wxpayId);
        if ($wxpay == null) {
            Log::error('find wxpay error. seller id :' . $wxpayId);
            return;
        }

        $wxpayService = new WxpayService($wxpay->mch_id, $wxpay->appid, $wxpay->key);
        $info = $wxpayService->notify();

        // $mch_id = $info->mch_id;  //微信支付分配的商户号
        // $appid = $info->appid; //微信分配的公众账号ID
        // $openid = $info->openid; //用户在商户appid下的唯一标识
        // $transaction_id = $info->transaction_id;//微信支付订单号
        // $out_trade_no = $info->out_trade_no;//商户订单号
        // $total_fee = $info->total_fee; //订单总金额，单位为分
        // $is_subscribe = $info->is_subscribe; //用户是否关注公众账号，Y-关注，N-未关注，仅在公众账号类型支付有效
        // $attach = $info->attach;//商家数据包，原样返回
        // $time_end = $info->time_end;//支付完成时间

        //金额单位转为元
        $totalFee = $info->total_fee / 100;

        $data = array(
            'mch_id' => $info->mch_id,
            'appid' => $info->appid,
            'out_trade_no' => $info->out_trade_no,
            'openid' => $info->openid,
            'wxpay_id' => $wxpay->id,
            'transaction_id' => $info->transaction_id,
            'total_fee' => $totalFee,
            'time_end' => $info->time_end,
            'created_at' => time(),
            'updated_at' => time(),
        );

        if (DB::insert(WxpayResult::$table, $data)) {
            $this->processOrder($info->out_trade_no, $totalFee);
        } else {
            Log::error('save wxpay_result error. ' . DB::getConnection()->getLastSql());
        }
    }

    /**
     * 订单支后成功后回调 检查订单状态，并更新状态 ，请勿在此方法中，输出任何html内容
     * @param string $outTradeNo 订单号
     * @param float $totalFee 支付金额(元)
     */
    public function processOrder($outTradeNo, $totalFee)
    {
        //子类重写此方法，处理订单逻辑
    }

}