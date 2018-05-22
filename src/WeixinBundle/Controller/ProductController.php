<?php
namespace WeixinBundle\Controller;

use EmployeeBundle\Entity\Employee;
use InterfaceBundle\Service\EmployeeService;
use InterfaceBundle\Service\ProductService;
use Rain\Auth;
use Rain\Pagination;
use Rain\Request;
use Rain\View;

/**
 * 服务控制器
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class ProductController
{
    /**
     * 服务列表页面
     */
    public function getIndex(Request $request)
    {
        if ($request->get('from') === 'androidApp') {
            header('Location: http://kangjun.chehutong.cn/app/#/product/index');
            return;
        }

        $displayStartAppAd =  (int)$request->cookies->get('start',0) == 1;
        return View::render('@WeixinBundle/product/index.twig',[
            'ad' => ProductService::findNewAd(),
            'gradeArr' => Employee::gradeParams(),
            'displayStartAppAd' => $displayStartAppAd
        ]);
    }


    /**
     * ajax加载服务列表
     */
    public function getAjaxList(Request $request)
    {
        $currentPage = $request->get('currentPage', 1);
        $page = Pagination::createFromCurrentNumber($currentPage);

         //星级
        $grade = $request->get('grade', null);

        //服务列表信息
        $result = ProductService::findList($page, (int)$grade);
        return json_encode($result);
    }


    /**
     * ajax判断技师是否存在
     */
    public function getAjaxEmployeeExists(Request $request)
    {
        $cityId = $request->get('cityId', 1);
        $productId = $request->get('productId', 0);
        $result = EmployeeService::findEmployeeIsExists($cityId, $productId);
        return json_encode($result);
    }

    /**
     * 服务详情页
     */
    public function getInfo(Request $request)
    {
        $id = $request->get('id');
        $product = ProductService::findInfo($id);
        return View::render('@WeixinBundle/product/info.twig', [
            'id' => $id,
            'product' => $product
        ]);
    }

}