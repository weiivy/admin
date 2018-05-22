<?php
namespace AppBundle\Service;

use AppBundle\Entity\Product;
use Rain\Pagination;

/**
 * Api服务类
 * 给app或微信端提供数据
 * Class ApiService
 * @package AppBundle\Service
 */
class ApiService
{
    /**
     * 服务项目列表
     * @param Pagination $page
     * @return array
     */
    public static function getAllProducts(Pagination $page)
    {
    }


    /**
     * 服务项目详情信息
     * @param $id
     * @return Product | null
     */
    public static function getProduct($id)
    {

    }



}