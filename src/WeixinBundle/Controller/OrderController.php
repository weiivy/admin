<?php

namespace WeixinBundle\Controller;

use Common\Entity\ActualPay;
use Common\Service\ActualPayService;
use InterfaceBundle\Entity\Comment;
use InterfaceBundle\Entity\Member;
use InterfaceBundle\Entity\Order;
use InterfaceBundle\Service\CommentService;
use InterfaceBundle\Service\OrderService;
use InterfaceBundle\Service\TicketService;
use Rain\Auth;
use Rain\Pagination;
use Rain\Redirect;
use Rain\Request;
use Rain\View;

/**
 * 订单控制器
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class OrderController
{
    /**
     * 订单列表
     */
    public function getIndex()
    {
        return View::render('@WeixinBundle/order/index.twig');
    }

    /**
     * 支付页面
     */
    public function getPay(Request $request)
    {
        $member = Auth::getIdentity(Member::className());
        $orderId = $request->get('id', 0);
        $order = OrderService::findOrderByMemberId($orderId, $member->id);
        $tickets = TicketService::findAllUnUserTicket($member->id);
        $actualPay = ActualPayService::findActualPayByOrderId($orderId);
        return View::render('@WeixinBundle/order/pay.twig', [
            'order' => $order,
            'payType' => Order::payTypeArr(),
            'tickets' => $tickets,
            'member' => $member,
            'discountPercent' => Member::getGradeDiscount($member->grade),
            'actualPay' => $actualPay
        ]);
    }


    /**
     * ajax添加订单
     */
    public function anyAjaxCreateOrder(Request $request)
    {
        $data = $request->get('Order');
        $result = OrderService::createOrder($data);
        return json_encode($result);
    }

    /**
     * 进行中订单列表
     */
    public function anyAjaxProgressing(Request $request)
    {
        $currentPage = $request->get("currentPage", 0);
        $page = Pagination::createFromCurrentNumber($currentPage);
        $member = Auth::getIdentity(Member::className());

        $result = OrderService::findOrderStatusProgressing($page, $member->id);
        return json_encode($result);
    }

    /**
     * 完成订单列表
     */
    public function anyAjaxSuccess(Request $request)
    {
        $currentPage = $request->get("currentPage", 0);
        $page = Pagination::createFromCurrentNumber($currentPage);
        $member = Auth::getIdentity(Member::className());

        $result = OrderService::findOrderStatusSuccess($page, $member->id);
        return json_encode($result);
    }

    /**
     * 取消订单列表
     */
    public function anyAjaxClose(Request $request)
    {
        $currentPage = $request->get("currentPage", 0);
        $page = Pagination::createFromCurrentNumber($currentPage);
        $member = Auth::getIdentity(Member::className());

        $result = OrderService::findOrderStatusCancel($page, $member->id);
        return json_encode($result);
    }

    /**
     * 订单详情
     */
    public function getInfo(Request $request)
    {
        $id = $request->get('id', 0);
        $member = Auth::getIdentity(Member::className());
        if($member == null){
             return Redirect::to('default/login');
        }
        $order = OrderService::findOrderByMemberId($id, $member->id);

        //实际支付总额
        $actualPayTotal = ActualPayService::findTotalPay($id);

        //卡券支付详情
        $cardPay = ActualPayService::findRecord($id, ActualPay::TYPE_CARD);
        return View::render('@WeixinBundle/order/info.twig',[
            'order' => $order,
            'cardPay' => $cardPay,
            'actualPayTotal' => $actualPayTotal
        ]);
    }

    /**
     * ajax取消订单
     */
    public function anyAjaxChangeCancel(Request $request)
    {
        $id = $request->get('id', 0);
        $result = OrderService::updateOrderForCancel($id);
        return json_encode($result);
    }


    /**
     * 当面付款
     */
    public function anyAjaxPayDelivery(Request $request)
    {
        $id = $request->get('id', 0);
        $ticketDetailId = $request->get("ticketDetailId", 0);
        $accountFlag = $request->get("accountFlag", false);

        $result = OrderService::updatePayType($id, $ticketDetailId, $accountFlag);
        return json_encode($result);
    }

    /**
     * 会员账号余额全额支付
     */
    public function anyAjaxPayAccount(Request $request)
    {
        $id = $request->get('id', 0);
        $ticketDetailId = $request->get("ticketDetailId", 0);
         $result = OrderService::updatePayAccount($id, $ticketDetailId);
        return json_encode($result);

    }

    /**
     * 评论页面
     */
    public function getComment(Request $request)
    {
        $id = $request->get('id', 0);
        $member = Auth::getIdentity(Member::className());
        if($member == null){
             return Redirect::to('default/login');
        }

        $comment = CommentService::isCommentByMemberIdAndOrderId($member->id, $id);
        return View::render("@WeixinBundle/order/comment.twig", [
            'comment' => $comment,
            'rateParams' => Comment::rateParams()
        ]);
    }

    /**
     * ajax评论
     */
    public function anyAjaxAddComment(Request $request)
    {
        $data['order_id'] = $request->get('id', 0);
        $data['rate'] = $request->get('rate', 0);
        $data['content'] = $request->get('content', ' ');
        $result = CommentService::addCreate($data);
        return json_encode($result);

    }

}