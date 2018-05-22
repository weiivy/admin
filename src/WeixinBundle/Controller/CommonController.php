<?php
namespace WeixinBundle\Controller;

use Common\Entity\ServiceTime;
use Common\Service\ServiceTimeService;
use InterfaceBundle\Entity\Comment;
use InterfaceBundle\Entity\Employee;
use InterfaceBundle\Entity\Member;
use InterfaceBundle\Entity\Order;
use InterfaceBundle\Service\EmployeeService;
use InterfaceBundle\Service\MemberService;
use InterfaceBundle\Service\ProductService;
use InterfaceBundle\Service\CommentService;

use Rain\Auth;
use Rain\Redirect;
use Rain\Request;
use Rain\View;

/**
 * 技师服务ajax验证登录方法公共控制器
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class CommonController
{
     /**
     * 服务立即预约页面
     */
    public function getOrder(Request $request)
    {
        $member = Auth::getIdentity(Member::className());
        $id = $request->get('id');
        $product = ProductService::findInfo($id);
        $timeArr = ServiceTime::getWorkTime();

        //地址列表
        $cities = MemberService::findAddressList($member->id);

        //默认地址
        $defaultAddress = MemberService::findDefaultAddress($member->id);

        //已近服务过的技师
        $employees = EmployeeService::findAlreadyServiceEmployee($id, $member->id);

        //处理星级价格
        $productGradePrice = [];
        $temp = ProductService::findAllGradeProductPrice($id);
        foreach($temp as $val) {
            $productGradePrice[$val->grade] = $val->price;
        }

        return View::render('@WeixinBundle/product/order.twig', [
            'id' => $id,
            'product' => $product,
            'timeArr' => $timeArr,
            'mobile' => $member->mobile,
            'cities' => $cities,
            'gradeArr' => ProductService::findProductAllPrice($id),
            'sexArr' => Employee::sexParams(),
            'defaultAddress' => $defaultAddress,
            'productGradePrice' => $productGradePrice,
            'employees' => $employees
        ]);
    }

    /**
     * ajax 收藏服务
     */
    public function anyAjaxCollectProduct(Request $request)
    {
        $data['product_id'] = $request->get('productId', 0);
        $result = ProductService::collectProduct($data);
        return json_encode($result);
    }


    /**
     * ajax 收藏技师
     */
    public function anyAjaxCollectEmployee(Request $request)
    {
        $data['employee_id'] = $request->get('employeeId', 0);

        $result = EmployeeService::collectEmployee($data);
        return json_encode($result);
    }

     /**
     * 技师服务预约页面
     */
    public function getProduct(Request $request)
    {
        $productId = $request->get('productId', 0);
        $employeeId = $request->get('employeeId', 0);
        $offline = $request->get('offline', 0);

        //登录会员信息
        $member = MemberService::findByPkFromMember(Auth::getId());

        //员工信息
        $employee = EmployeeService::findInfo($employeeId);
        $gradeEmployee = EmployeeService::findEmployee($employeeId);

        //服务信息
        $product = ProductService::findProductGradePrice($productId, $gradeEmployee->grade);

        //地址列表
        $cities = MemberService::findAddressList($member->id);

        //获取某技师的某个服务的评论
        $commentCount = CommentService::findAllCommentCount($productId, $employeeId);

        //技师不可预约时间
        $noServiceArr = ServiceTimeService::findServiceTimeThreeRecord($employeeId);

        if($offline > 0) {

            //全天时间段
            $timeArr =  [
                '00:00','00:30','01:00', '01:30', '02:00', '02:30', '03:00','03:30', '04:00', '04:30', '05:00',
                '05:30', '06:00', '06:30', '07:00','07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
                '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00',
                '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00',
                '19:30', '20:00', '20:30', '21:00', '21:30','22:00', '22:30', '23:00','23:30'
            ];
        } else {
            $timeArr = ServiceTime::getWorkTime();
        }

        //默认地址
        $defaultAddress = MemberService::findDefaultAddress($member->id);

        //技师总评数
        $total = CommentService::findAllCommentCountByEmployeeId($employeeId);

        //技师好评数
        $goodComment = CommentService::findAllGoodCommentCount($employeeId);

        $checkedTime = static::packagingBookingTime($noServiceArr, $timeArr, $product);

        return View::render('@WeixinBundle/employee/product.twig', [
            'productId' => $productId,
            'employeeId' => $employeeId,
            'employee' => $employee,
            'member' => $member,
            'product' => $product,
            'cities' => $cities,
            'timeArr' => $timeArr,
            'checkedTime' => $checkedTime,
            'commentCount' => $commentCount,
            'defaultAddress' => $defaultAddress,
            'commentGrade' => Comment::grade($total, $goodComment)
        ]);
    }

    /**
     * 组装预约禁止时间
     * @param $noServiceArr
     * @param $timeArr
     * @param $product
     * @return array
     */
    protected static function packagingBookingTime($noServiceArr, $timeArr, $product)
    {
        //当前的时间
        $current = date("H:i", strtotime('+1 hours'));  //时分  提前1.5小时预约
        $date = date("Y-m-d", time());   //年月日
        $checkTime = [];

        //没有预约时间
        if (empty($noServiceArr)) {
            foreach ($timeArr as $k => $v) {

                //已过时间段
                if ($current > $v) {
                    $checkTime[$date][] = $v;
                }
            }
        } else {

            //有预约时间
            foreach ($noServiceArr as $val) {
                $temp = [];
                foreach ($timeArr as $k => $v) {

                    //已过时间段
                    if ($val->date == $date && $current > $v) {
                        $temp[] = $v;
                    }

                    //技师不可预约时间
                     $timeslot = ServiceTime::aheadTime($val->timeslot);
                    //if (in_array($v, ServiceTime::getTimeForMember($val->timeslot))) {
                    if (in_array($v, ServiceTime::getTimeForMember($timeslot, $timeArr))) {
                        $temp[] = $timeArr[$k];
                    }
                }
                $checkTime[$val->date] = array_unique($temp);
            }
        }

        return $checkTime;
    }
}