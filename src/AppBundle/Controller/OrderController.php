<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Order;
use AppBundle\Service\BankService;
use AppBundle\Service\EmployeeService;
use AppBundle\Service\OrderItemService;
use AppBundle\Service\OrderService;
use Common\Entity\ActualPay;
use Common\Service\ActualPayService;
use InterfaceBundle\Service\NotifyService;
use Rain\Application;
use Rain\Pagination;
use Rain\Request;
use Rain\View;

/**
 * 订单管理控制器
 *
 * @author Zhang Weiwei
 * @since 1.0
 */
class OrderController
{
    /**
     * 订单列表
     */
    public function getIndex(Request $request)
    {
        $p = $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $condition = [];
        $params = [];

        //订单号搜索
        $outTradeNo = $request->get('out_trade_no');
        if($outTradeNo){
            $condition[] = 'o.out_trade_no = :no';
            $params[':no'] = $outTradeNo;
        }

        $mobile = $request->get('mobile');
        if($mobile){
            $condition[] = 'm.mobile = :mobile';
            $params[':mobile'] = $mobile;
        }

        $status = $request->get('status');
        if($status){
            $condition[] = 'o.status = :status';
            $params[':status'] = $status;
        }

        $bank = $request->get('bank');
        if(!empty($bank)) {
            $condition[] = 'o.bank_id= :bank_id';
            $params[':bank_id'] = $bank;
        }

        $banks = BankService::getBankKey();

        $list = OrderService::findOrderList($page, implode(' and ', $condition), $params);
        return View::render('@AppBundle/order/index.twig',[
            'list' => $list,
            'page' => $page,
            'banks' => $banks
        ]);
    }

    /**
     * 订单详情
     */
    public function getView(Request $request, Application $app)
    {

        //订单详情
        $id = $request->get('id', 0);
        $order = OrderService::findOrder($id);
        if($order == null){
            $app->abort(404);
        }

        //订单明细
        $orderPhotos = OrderService::findOrderPhoto($order);
        $banks = BankService::getBankKey();
        return View::render('@AppBundle/order/view.twig', [
            'order' => $order,
            'orderPhotos' => $orderPhotos,
            'banks' => $banks
        ]);
    }


    /**
     * 确认订单
     */
    public function anyAjaxReviewing(Request $request)
    {
        $result = array(
            'status' => 0,
            'message' => ""
        );

        //获取订单
        $id = $request->get("id");
        $order = OrderService::findOrder($id);
        if ($order == null) {
            $result['message'] = "订单不存在";
            return json_encode($result);
        }

        $errors = [];
        if(OrderService::updateOrderWidthReviewing($id, $errors)){

            $result['status'] = 1;
            $result['message'] = "订单审核中";

        } else {
            $result['message'] = $errors[0];
        }
        return json_encode($result);

    }


    /**
     * 完成
     */
    public function anyAjaxSuccess(Request $request)
    {
        $result = array(
            'status' => 0,
            'message' => ""
        );

        //获取订单
        $id = $request->get("id");
        $money = $request->get("money");
        if($money > 1000) {
            $result['message'] = "订单异常请检查";
            return json_encode($result);
        }
        $order = OrderService::findOrder($id);
        if ($order == null) {
            $result['message'] = "订单不存在";
            return json_encode($result);
        }

        $errors = [];
        if(OrderService::updateOrderWithSuccess($id, $money, $errors)){

            $result['status'] = 1;
            $result['message'] = "订单审核成功";

        } else {
            $result['message'] = $errors[0];
        }

        return json_encode($result);

    }

    /**
     * ajax分配技师
     */
    public function anyAjaxFail(Request $request)
    {
         $result = array(
            'status' => 0,
            'message' => ""
         );

        //获取订单
        $id = $request->get("id");
        $order = OrderService::findOrder($id);
        if ($order == null) {
            $result['message'] = "订单不存在";
            return json_encode($result);
        }

        if (OrderService::updateOrderWithFail($id, $errors)) {
            $result['status'] = 1;
            $result['message'] = "订单审核失败";
        } else {
            $result['message'] = $errors[0];
        }

        return json_encode($result);
    }

    public function getCreate(Request $request)
    {
        $mid = $request->get('mid');
        $banks = BankService::getBankKey();
        return View::render('@AppBundle/order/add.twig',[
            'banks' => $banks,
            'mid' => $mid,
        ]);
    }

}