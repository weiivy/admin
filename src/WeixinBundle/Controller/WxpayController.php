<?php

namespace WeixinBundle\Controller;

use AppBundle\Service\OrderItemService;
use Common\Entity\ActualPay;
use Common\Entity\Consumption;
use Common\Entity\Member;
use Common\Entity\Order;
use Common\Service\ActualPayService;
use Common\Service\ConsumptionService;
use Common\Service\UseService;
use InterfaceBundle\Service\EmployeeService;
use InterfaceBundle\Service\MemberService;
use InterfaceBundle\Service\NotifyService;
use InterfaceBundle\Service\TicketService;
use Pay\Wxpay\WxpayService;
use Rain\Application;
use Rain\Auth;
use Rain\DB;
use Rain\Log;
use Rain\Request;
use Rain\Session;
use Rain\Url;
use Rain\View;
use Rain\Wechat\Api;
use InterfaceBundle\Service\OrderService;

/**
 * 微信支付
 * @author  Zou Yiliang
 * @since   1.0
 */
class WxpayController
{

    //微信支付 必须使用微信浏览器打开
    public function getPay(Request $request, Application $app)
    {
        $order = OrderService::findOrder($request->get('orderId'));
        if ($order === null) {
            $app->abort(500, "订单ID错误");
        }

        $accountFlag = $request->get('accountFlag', false);
        $ticketDetailId = $request->get('ticketDetailId', 0);
        $totalFee = $order->total_fee;

        /*-----------------------------卡券支付开始------------------------------------*/
        //获取卡券信息
        $ticket = null;
        if ($ticketDetailId > 0) {
            $ticket = TicketService::findTicketInfo($ticketDetailId);
            if ($ticket == null) {
                $app->abort(500, "卡券ID错误");
            }
        }

        //检测原来是否有使用卡券记录
        $actualPayCard = ActualPayService::findRecord($order->id, ActualPay::TYPE_CARD);

        //原来使用卡券，现在不使用卡券,删除原来的卡券记录
        if ($actualPayCard != null && $ticket == null) {
            if(!ActualPayService::deleteCardRecord($actualPayCard->id, $order->id, $order->member_id)) {
                DB::getConnection()->rollBack();
                return;
            }
        }

        //原来使用卡券，现在使用卡券,修改新卡券使用记录
        if ($actualPayCard != null && $ticket->reduce_cost != $actualPayCard->fee) {
            if ($order->total_fee >= $ticket->least_cost) {
                if(!ActualPayService::updateCardRecord($actualPayCard->id, $order->id, $ticket->ticket_id, $ticketDetailId, $order->member_id)) {
                    DB::getConnection()->rollBack();
                    return;
                }

                //标记会员卡券为使用
                TicketService::updateTicketStatus($ticketDetailId);

                //添加卡券使用记录
                $cardRecord = ActualPayService::findRecord($order->id, ActualPay::TYPE_CARD);
                if ($cardRecord != null && $ticket != null) {
                    $useArr['ticket_id'] = $ticket->ticket_id;
                    $useArr['actual_pay_id'] = $cardRecord->id;
                    UseService::addTicketUse($useArr);
                }
            } else {
                if(!ActualPayService::deleteCardRecord($actualPayCard->id, $order->id, $order->member_id)) {
                    DB::getConnection()->rollBack();
                    return;
                }
            }
        }

        //原来未使用卡券，现在使用卡券
        if ($actualPayCard == null && $ticket != null) {

            if ($order->total_fee >= $ticket->least_cost) {

                //添加实际支付记录
                $actualPays['order_id'] = $order->id;
                $actualPays['type'] = ActualPay::TYPE_CARD;
                $actualPays['fee'] = $ticket->reduce_cost;
                $actualPays['remark'] = $ticket->notice;
                $actualPays['status'] = ActualPay::STATUS_USED;
                if(!ActualPayService::addRecord($actualPays)) {
                    DB::getConnection()->rollBack();
                    return;
                }

                //修改会员的某卡券状态
                TicketService::updateTicketStatus($ticketDetailId);

                //添加卡券使用记录
                $cardRecord = ActualPayService::findRecord($order->id, ActualPay::TYPE_CARD);
                if ($cardRecord != null && $ticket != null) {
                    $useArr['ticket_id'] = $ticket->ticket_id;
                    $useArr['actual_pay_id'] = $cardRecord->id;
                    UseService::addTicketUse($useArr);
                }

                //满足减免条件时，减去相应的优惠价格
                $totalFee = $order->total_fee - $ticket->reduce_cost;
                $totalFee = $totalFee;
            } else {
                $totalFee = $order->total_fee;
            }
        }

        if ($actualPayCard != null && $ticket->reduce_cost == $actualPayCard->fee) {
            $totalFee = $totalFee - $actualPayCard->fee;
        }

        /*-----------------------------卡券支付结束------------------------------------*/

        //打折
        $orderItem = OrderItemService::findOrderItemByOrderId($order->id)[0];
        $totalFee = Member::discountPrice($order->id, $order->member_id, $totalFee, $orderItem->product_id);

        //是否要加额外服务费
        if ($order->extra_fee > 0) {
            $totalFee = $totalFee + $order->extra_fee;
        }

        /*-------------------------------账号支付开始----------------------------------*/
        //使用账号余额
        $member = DB::select(Member::tableName())->asEntity(Member::className())->lockForUpdate()->findByPk($order->member_id);

        //账号支付记录
        $actualPayAccount = ActualPayService::findRecord($order->id, ActualPay::TYPE_ACCOUNT);

        //原来使用账号支付，现在不使用账号支付
        if ($actualPayAccount != null && $accountFlag == "false") {
            if(!ActualPayService::deleteRecordByType($actualPayAccount->id, $order->id, ActualPay::TYPE_ACCOUNT)) {
                DB::getConnection()->rollBack();
                return;
            }
        }

        //两次账号支付金额不一致，修改账号支付记录价格
        if ($actualPayAccount != null && $accountFlag == "true" && $totalFee > $member->money && $member->money != $actualPayAccount->fee) {
            $totalFee = $totalFee - $member->money;
            if(!ActualPayService::updateAccountRecord($actualPayAccount->id, $order->id, $member->money)) {
                DB::getConnection()->rollBack();
                return;
            }
        }

        //原来没有使用账号支付
        if ($actualPayAccount == null && $accountFlag == "true" && $totalFee > $member->money) {
            $totalFee = $totalFee - $member->money;

            //添加实际支付记录
            $actualPays['order_id'] = $order->id;
            $actualPays['type'] = ActualPay::TYPE_ACCOUNT;
            $actualPays['fee'] = $member->money;
            $actualPays['remark'] = '账号支付';
            $actualPays['status'] = ActualPay::STATUS_USED;
            if(!ActualPayService::addRecord($actualPays)) {
                DB::getConnection()->rollBack();
                return;
            }
        }

        if ($actualPayAccount != null && $accountFlag == "true" && $totalFee > $member->money && $member->money == $actualPayAccount->fee) {
            $totalFee = $totalFee - $actualPayAccount->fee;
        }

        /*------------------------------账号支付结束-----------------------------------*/


        /*---------------------------微信支付开始--------------------------------------*/
        //微信支付
        $actualPayWeChat = ActualPayService::findRecord($order->id, ActualPay::TYPE_WECHAT);
        $otherPay = ActualPayService::findOtherPayMoney($order->id, ActualPay::TYPE_WECHAT);
        $totalFee = $order->total_fee - $otherPay;

        //两次微信支付金额不一致
        if ($actualPayWeChat != null && $actualPayWeChat->fee != $totalFee) {
            if(!ActualPayService::updateWeCahrRecord($actualPayWeChat->id, $order->id, $totalFee)) {
                DB::getConnection()->rollBack();
                return;
            }
        }

        //原来未使用微信支付
        if ($actualPayWeChat == null) {
            //添加实际支付记录
            $actualPays['order_id'] = $order->id;
            $actualPays['type'] = ActualPay::TYPE_WECHAT;
            $actualPays['fee'] = $totalFee;
            $actualPays['remark'] = '微信支付';
            $actualPays['status'] = ActualPay::STATUS_UNUSED;
            if(!ActualPayService::addRecord($actualPays)) {
                DB::getConnection()->rollBack();
                return;
            }
        }

        /*--------------------------微信支付结束---------------------------------------*/

        //微信需要支付金额
        $actualPayWeChatNew = ActualPayService::findRecord($order->id, ActualPay::TYPE_WECHAT);
        $weChatPay = $actualPayWeChatNew->fee;

        $config = $app['params']['wxpay'];
        $payService = new WxpayService($config['mch_id'], $config['appid'], $config['key']);

        $openid = $this->getOpenid();

        //存取会员的openid
        if (empty($member->openid)) {
            $data['openid'] = $openid;
            MemberService::saveMemberOpenid($data, $member->id);
        }


        //微信js sdk
        $official = Application::$app['params']['weixin'];
        $api = new \Rain\Wechat\Api($official['appId'], $official['appSecret']);
        $signPackage = $api->getSignPackage();
        //微信支付
        $notifyUrl = Url::to('wxpay/notify', array(), true); // 通知url
        $bizPackage = $payService->createJsBizPackage($openid, $weChatPay, $order->out_trade_no, $order->name, $notifyUrl, $signPackage['timestamp']);

        $orderInfo = OrderService::findOrderByMemberId($order->id, $order->member_id);

        return View::render('@WeixinBundle/wxpay/pay.twig', [
            'order' => $order,
            'orderInfo' => $orderInfo,
            'signPackage' => $signPackage,
            'bizPackage' => $bizPackage,
            'ticket' => $ticket,
            'weChat' => $actualPayWeChatNew
        ]);
    }


    //微信支付结果异步通知
    public function anyNotify()
    {
        $config = Application::$app['params']['wxpay'];
        $wxpayService = new WxpayService($config['mch_id'], $config['appid'], $config['key']);
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
            'transaction_id' => $info->transaction_id,
            'total_fee' => $totalFee,
            'time_end' => $info->time_end,
            'created_at' => time(),
            'updated_at' => time(),
        );

        if (DB::insert('wxpay_result', $data)) {
            $this->processOrder($info->out_trade_no, $totalFee);
        } else {
            Log::error('save wxpay_result error. ' . DB::getConnection()->getLastSql());
        }
    }

    protected function processOrder($outTradeNo, $totalFee)
    {
        DB::getConnection()->beginTransaction();
        $order = DB::select(Order::tableName())->lockForUpdate()->asEntity(Order::className())->find('out_trade_no = ?', [$outTradeNo]);

        if ($order == null) {
            Log::error('find order error. out_trade_no: ' . $outTradeNo);
            DB::getConnection()->rollBack();
            return;
        }

        //已支付，不做处理，为重复通知
        if ($order->payment_status == Order::PAYMENT_STATUS_YES) {
            Log::warning('重复的支付通知. order id: ' . $order->id);
            DB::getConnection()->rollBack();
            return;
        }

        //获取该订单实际支付金额
        //$orderTotalFee = ActualPayService::findWeChatByOrderId($order->id);

        //原调用的是 ActualPayService::findWeChatByOrderId 方法，使用$orderTotalFee与$totalFee比较
        //但该方法中，未加status字段，所以在这里从新写了代码
        //订单应付金额 (微信支付未支付)
        $actualPay = DB::select(ActualPay::tableName())
            ->asEntity(ActualPay::className())
            ->find('order_id = ? and type = ? and status=?', [$order->id, ActualPay::TYPE_WECHAT, ActualPay::STATUS_UNUSED]);
        if ($actualPay == null) {
            Log::warning('收到微信支付通知，但该订单无微信应付记录' . $outTradeNo);
            DB::getConnection()->rollBack();
            return;
        }

        //支付金额不对
        if ($actualPay->fee != $totalFee) {

            //原代码:
            Log::error(sprintf('微信实际支付与应付款不相等。订单id:%d,应付%f,实付%f', $order->id, $actualPay->fee, $totalFee));
            DB::getConnection()->rollBack();
            return;

            //由于支付的bug，有时实际微信支付金额与应付不相等，相差0.01元
            //这里临时做兼容处理，只要不超过0.05元，均认为支付有效
            //支付bug修复后，改为使用上面的原代码，删除此临时代码
            //邹义良  20150930
            /*Log::warning(sprintf('微信实际支付与应付款不相等。订单id:%d,应付%f,实付%f', $order->id, $actualPay->fee, $totalFee));
            if (abs($actualPay->fee - $totalFee) > 0.05) {
                DB::getConnection()->rollBack();
                return;
            }*/

        }

        //改为订单已支付状态
        $data['payment_status'] = Order::PAYMENT_STATUS_YES;
        $data['pay_type'] = Order::PAY_TYPE_WECHAT;
        $data['updated_at'] = time();

        if (DB::update(Order::tableName(), $data, 'id = ?', [$order->id]) != 1) {
            Log::error('update order status error. id: ' . $order->id);
            DB::getConnection()->rollBack();
            return;
        }

        //修改微信微信的卡券状态
        $actualPayWeChat = ActualPayService::findRecord($order->id, ActualPay::TYPE_WECHAT);
        if ($actualPayWeChat == null) {
            Log::error('select order actualPayRecord error. id: ' . $order->id);
            DB::getConnection()->rollBack();
            return;
        }

        if(!ActualPayService::updateWeChatStatus($actualPayWeChat->id, $order->id)){
            Log::error("修改微信实际支付记录失败失败\n" . DB::getLastSql());
            DB::getConnection()->rollBack();
            return;
        }


        $actualPayAccount = ActualPayService::findRecord($order->id, ActualPay::TYPE_ACCOUNT);
        if ($actualPayAccount != null) {
            $member = DB::select(Member::tableName())->asEntity(Member::className())->lockForUpdate()->findByPk($order->member_id);
            //扣钱
            if (!Member::editMoney($member->id, -$member->money)) {
                Log::error("使用当面支付时使用账号余额, 修改会员金额失败\n" . DB::getLastSql());
                DB::getConnection()->rollBack();
                return;
            }

            //新增会员消费纪录
            $consumptionArr = [
                'member_id' => $member->id,
                'amount' => -$member->money,
                'type' => Consumption::TYPE_ORDER,
                'relation_id' => $order->id,
                'content' => "服务消费－" . $order->name,
            ];
            $error = '';
            if (!ConsumptionService::addRecord($consumptionArr, $error)) {
                Log::error("微信支付使用账号支付一部分时，新增会员消费纪录失败\n" . DB::getLastSql());
                DB::getConnection()->rollBack();
                return;
            }
        }

        if ($order->employee_id != 0 && !NotifyService::EmployeeConfirmOrder($order->employee_id, $order->id)) {
            Log::error('send message for employee error. id: ' . $order->id);
        }
        DB::getConnection()->commit();
    }


    //获取用户的openid
    protected function getOpenid()
    {
        $openid = Session::get('openid-session', null);
        if ($openid !== null) {
            return $openid;
        }

        $api = new Api(Application::$app['params']['weixin']);

        $middleUrl = Application::$app['params']['authorizeRedirectUrl'];
        $arr = $api->getOpenAuthUserInfo(true, $middleUrl);

        if (isset($arr['openid'])) {
            Session::put('openid-session', $arr['openid']);
            return $arr['openid'];
        }
        throw new \Exception('获取openid失败');
    }


    //手动修复微信支付
    //由于某些原因导致微信订单已支付时，订单状态未修改成功，
    //解决完问题后，手动调用此方法，自动修改订单状态(执行一次支付成功回调后的业务逻辑)。
    //http://kangjun.chehutong.cn/weixin.php/wxpay/check-wxpay?out_trade_no=5606C4279C4D
    //注意，访问此方法，不需要登录验证
    //邹义良 20150929
    public function getCheckWxpay(Request $request)
    {
        header("Content-type: text/html; charset=utf-8");

        $out_trade_no = $request->get('out_trade_no');
        $order = DB::select(Order::tableName())->asEntity(Order::className())->find('out_trade_no = ?', [$out_trade_no]);

        if ($order == null) {
            return '未找到订单';
        }

        //查看订单的支付状态
        if ($order->payment_status == Order::PAYMENT_STATUS_YES) {
            return '已支付成功，不需要处理';
        }

        //查询微信支付记录
        $wxpay_result = DB::select('{{pre_wxpay_result}}')->find('out_trade_no=?', [$out_trade_no]);

        if ($wxpay_result == null) {
            return '未找到订单的微信支付记录';

        }

        //支付后的业务逻辑
        $this->processOrder($out_trade_no, $wxpay_result['total_fee']);

        //显示处理后该订单的支付状态
        $order = DB::select(Order::tableName())->asEntity(Order::className())->find('out_trade_no = ?', [$out_trade_no]);
        return $order->payment_status == Order::PAYMENT_STATUS_YES ? '已完成支付' : '未完成支付';


    }
}