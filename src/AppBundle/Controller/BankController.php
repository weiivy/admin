<?php
namespace AppBundle\Controller;
use AppBundle\Entity\Bank;
use AppBundle\Service\BankService;
use Rain\Application;
use Rain\Pagination;
use Rain\Redirect;
use Rain\Request;
use Rain\Session;
use Rain\View;

/**
 * 银行控制器
 * @copyright (c) 2018, lulutrip.com
 * @author  Ivy Zhang<ivyzhang@lulutrip.com>
 */
class BankController
{
    /**
     * 银行列表
     */
    public function getIndex(Request $request)
    {

        $p= $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        return View::render('@AppBundle/bank/list.twig', [
            'list' => BankService::findAllBank($page),
            'page' => $page
        ]);
    }

    /**
     * 新增
     */
    public function anyCreate(Request $request)
    {
        $errors = [];
        if($request->isMethod('post')) {
            $data = $request->get("Bank");
            if(BankService::addBank($data, $errors)) {
                Session::setFlash('message', "新增成功");
                return Redirect::to('bank/index');
            }
        }

        return View::render('@AppBundle/bank/create.twig', [
            'errors' => $errors,
            'statusArr' => Bank::statusParams()
        ]);

    }

    /**
     * 修改
     */
    public function anyUpdate(Request $request, Application $app)
    {
        $errors = [];
        $id = $request->get('id', 0);
        $bank = BankService::findBank($id);
        if($bank == null) {
            $app->abort(404);
        }

        if($request->isMethod('post')) {
            $data = $request->get("Bank");
            if(BankService::updateBank($id, $data, $errors)) {
                Session::setFlash('message', "修改成功");
                return Redirect::to('bank/index');
            }
        }

        return View::render('@AppBundle/bank/update.twig', [
            'errors' => $errors,
            'bank' => $bank,
            'statusArr' => Bank::statusParams()
        ]);
    }

    /**
     * 删除银行
     */
    public function getDelete(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $bank= BankService::findBank($id);
        if($bank == null) {
            $app->abort(404);
        }

        $errors = [];
        if(BankService::deleteBank($bank, $errors)) {
            Session::setFlash('message', "删除成功");
            return Redirect::to('bank/index');
        } else {
            Session::setFlash('message', "删除失败");
            return Redirect::to('bank/index');
        }
    }

}