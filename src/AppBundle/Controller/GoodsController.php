<?php

namespace AppBundle\Controller;


use AppBundle\Entity\BankGoods;
use AppBundle\Service\BankGoodsService;
use AppBundle\Service\BankService;
use Rain\Application;
use Rain\Pagination;
use Rain\Redirect;
use Rain\Request;
use Rain\Session;
use Rain\View;

class GoodsController
{
    public $grade = [
        1 => '会员',
        2 => '代理',
        3 => '股东',
    ];
    /**
     * 列表页
     */
    public function getIndex(Request $request)
    {
        $p= $request->get('page', 1);
        $bankId = $request->get('bank');
        $condition = $params = [];
        if(!empty($bankId)) {
            $condition[] = 'bank_id=:bank_id';
            $params[':bank_id'] = $bankId;
        }
        $page = Pagination::createFromCurrentNumber($p);
        return View::render('@AppBundle/goods/list.twig', [
            'list' => BankGoodsService::findBankGoodsList($page, $condition, $params),
            'page' => $page,
            'banks' => BankService::getBankKey(),
            'grade' => $this->grade
        ]);
    }

    /**
     * 新增
     */
    public function anyCreate(Request $request)
    {
        $errors = [];
        if($request->isMethod('post')) {
            $data = $request->get("Goods");
            $data['money'] = $data['money'] ? json_encode($data['money']) : '';
            if(BankGoodsService::addBankGoods($data, $errors)) {
                Session::setFlash('message', "新增成功");
                return Redirect::to('goods/index');
            }
        }

        return View::render('@AppBundle/goods/create.twig', [
            'errors' => $errors,
            'statusArr' => BankGoods::statusParams(),
            'banks' => BankService::getBankKey(),
            'grade' => $this->grade
        ]);

    }

    /**
     * 修改
     */
    public function anyUpdate(Request $request, Application $app)
    {
        $errors = [];
        $id = $request->get('id', 0);
        $goods = BankGoodsService::findBankBankGoods($id);
        if($goods == null) {
            $app->abort(404);
        }
        $goods['money'] = $goods['money'] ? json_decode($goods['money'], true) : [];

        if($request->isMethod('post')) {
            $data = $request->get("Goods");
            $data['money'] = $data['money'] ? json_encode($data['money']) : '';
            if(BankGoodsService::updateBankGoods($id, $data, $errors)) {
                Session::setFlash('message', "修改成功");
                return Redirect::to('goods/index');
            }

        }

        return View::render('@AppBundle/goods/update.twig', [
            'errors' => $errors,
            'goods' => $goods,
            'statusArr' => BankGoods::statusParams(),
            'banks' => BankService::getBankKey(),
            'grade' => $this->grade
        ]);
    }

    /**
     * 删除银行
     */
    public function getDelete(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $goods= BankGoodsService::findBankBankGoods($id);
        if($goods == null) {
            $app->abort(404);
        }

        $errors = [];
        if(BankGoodsService::deleteBankGoods($goods, $errors)) {
            Session::setFlash('message', "删除成功");
            return Redirect::to('goods/index');
        } else {
            Session::setFlash('message', "删除失败");
            return Redirect::to('goods/index');
        }
    }
}