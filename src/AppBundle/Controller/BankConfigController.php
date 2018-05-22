<?php
namespace AppBundle\Controller;
use AppBundle\Entity\BankConfig;
use AppBundle\Service\BankConfigService;
use AppBundle\Service\BankService;
use Rain\Application;
use Rain\Pagination;
use Rain\Redirect;
use Rain\Request;
use Rain\Session;
use Rain\View;

/**
 * message
 * @copyright (c) 2018, lulutrip.com
 * @author  Ivy Zhang<ivyzhang@lulutrip.com>
 */
class BankConfigController
{
    /**
     * 银行列表
     */
    public function getIndex(Request $request)
    {

        $condition = $params = [];
        $bank = $request->get('bank');
        if(!empty($bank)) {
            $condition[] = 'bank = :bank';
            $params[':bank'] = $bank;
        }

        $type = $request->get('type');
        if(!empty($type)) {
            $condition[] = 'type = :type';
            $params[':type'] = $type;
        }

        $p= $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $banks = BankService::getBankKey();
        return View::render('@AppBundle/bank-config/list.twig', [
            'list' => BankConfigService::findBankConfigList($page, $condition, $params),
            'page' => $page,
            'banks' => $banks,
            'typeArr'   => BankConfig::typeParams(),
        ]);
    }

    /**
     * 新增
     */
    public function anyCreate(Request $request)
    {
        $errors = [];
        if($request->isMethod('post')) {
            $data = $request->get("BankConfig");
            if(BankConfigService::addBankConfig($data, $errors)) {
                Session::setFlash('message', "新增成功");
                return Redirect::to('bank-config/index');
            }
        }

        return View::render('@AppBundle/bank-config/create.twig', [
            'errors' => $errors,
            'statusArr' => BankConfig::statusParams(),
            'typeArr'   => BankConfig::typeParams(),
            'banks' => BankService::getBankKey()
        ]);

    }

    /**
     * 修改
     */
    public function anyUpdate(Request $request, Application $app)
    {
        $errors = [];
        $id = $request->get('id', 0);
        $bankConfig = BankConfigService::findBankConfig($id);
        if($bankConfig == null) {
            $app->abort(404);
        }

        if($request->isMethod('post')) {
            $data = $request->get("BankConfig");
            if(BankConfigService::updateBankConfig($id, $data, $errors)) {
                Session::setFlash('message', "修改成功");
                return Redirect::to('bank-config/index');
            }
        }

        return View::render('@AppBundle/bank-config/update.twig', [
            'errors' => $errors,
            'bankConfig' => $bankConfig,
            'statusArr' => BankConfig::statusParams(),
            'typeArr'   => BankConfig::typeParams(),
            'banks' => BankService::getBankKey()
        ]);
    }

    /**
     * 删除银行
     */
    public function getDelete(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $bankConfig = BankConfigService::findBankConfig($id);
        if($bankConfig == null) {
            $app->abort(404);
        }

        $errors = [];
        if(BankConfigService::deleteBankConfig($bankConfig, $errors)) {
            Session::setFlash('message', "删除成功");
            return Redirect::to('bank-config/index');
        } else {
            Session::setFlash('message', "删除失败");
            return Redirect::to('bank-config/index');
        }
    }
}