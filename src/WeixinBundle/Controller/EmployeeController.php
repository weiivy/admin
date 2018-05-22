<?php

namespace WeixinBundle\Controller;

use Common\Entity\City;
use InterfaceBundle\Entity\Comment;
use InterfaceBundle\Entity\Employee;
use InterfaceBundle\Service\CommentService;
use InterfaceBundle\Service\EmployeeService;
use InterfaceBundle\Service\ProductService;
use Rain\Auth;
use Rain\Pagination;
use Rain\Request;
use Rain\View;

/**
 * 技师控制器
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class EmployeeController
{
    public function getIndex()
    {
        return View::render('@WeixinBundle/employee/index.twig',[
            'gradeArr' => Employee::gradeParams()
        ]);
    }

    /**
     * ajax 获取技师列表
     */
    public static function getAjaxList(Request $request)
    {

        $condition[] = "city_id = :cid";
        $params[':cid'] = $request->get('cityId', 1);

        //星级
        $grade = $request->get('grade', null);
        if($grade != null && $grade > 0) {
             $condition[] = "grade = :grade";
             $params[':grade'] = $grade;
        }

        $currentPage = $request->get('currentPage', 1);
        $page = Pagination::createFromCurrentNumber($currentPage);

        //技师列表
        $result = EmployeeService::findList($page, $condition, $params);
        return json_encode($result);

    }

    /**
     * 技师详情
     */
    public function getInfo(Request $request)
    {
        $id = $request->get('id', 0);

        //标记是否来自微信
        $offline = $request->get('offline', null);
        $employee = EmployeeService::findInfo($id);

        //技师总评数
        $commentCount = CommentService::findAllCommentCountByEmployeeId($id);

        //技师好评数
        $goodComment = CommentService::findAllGoodCommentCount($id);
        $data = [
            'id' => $id,
            'employee' => $employee,
            'commentCount' => $commentCount,
            'commentGrade' => Comment::grade($commentCount, $goodComment)
        ];
        if($offline != null) {
            $data['offline'] = $offline;
        }

        return View::render('@WeixinBundle/employee/info.twig', $data);
    }

    /**
     * 加载技师评论页面
     */
    public function getEmployeeComment(Request $request)
    {
        $id = $request->get('id', 0);
        return View::render('@WeixinBundle/employee/employee-comment.twig', [
            'id' => $id
        ]);

    }

    /**
     * ajax获取技师评论
     */
    public function anyAjaxEmployeeComment(Request $request)
    {
        $id = $request->get('id', 0);
        $p = $request->get('currentPage', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $result = CommentService::findAllCommentByEmployeeId($id, $page);
        return json_encode($result);
    }

    /**
     * 加载技师服务评论页面
     */
    public function getEmployeeProductComment(Request $request)
    {
        $productId = $request->get('productId', 0);
        $employeeId = $request->get('employeeId', 0);
        return View::render('@WeixinBundle/employee/employee-product-comment.twig', [
            'productId' => $productId,
            'employeeId' => $employeeId

        ]);

    }

    /**
     * ajax获取技师服务评论
     */
    public function anyAjaxEmployeeProductComment(Request $request)
    {
        $productId = $request->get('productId', 0);
        $employeeId = $request->get('employeeId', 0);
        $p = $request->get('currentPage', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $result = CommentService::findAllComment($productId, $employeeId, $page);
        return json_encode($result);
    }

    /**
     * ajax 获取技师服务
     */
    public function getAjaxEmployeeProducts(Request $request)
    {
        //技师id
        $employeeId = $request->get('employeeId', 0);

        //排序条件
        $orderBy = $request->get('orderBy', null);
        $result = ProductService::findProductByEmployeeIds($employeeId, $orderBy);
        return json_encode($result);
    }


    /**
     * ajax 修改城市
     */
    public static function anyAjaxChangeCity(Request $request)
    {
        //城市信息
        $defaultCity = $request->get('defaultCity');
        $result = City::cityInfo($defaultCity);
        return json_encode($result);
    }



}