<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Member;
use AppBundle\Service\MemberService;
use Common\Service\ActualPayService;
use Rain\Application;
use Rain\Pagination;
use Rain\Redirect;
use Rain\Request;
use Rain\Session;
use Rain\UploadedFile;
use Rain\Url;
use Rain\View;

/**
 * 会员管理控制器
 *
 * @author Zhang Weiwei
 * @since 1.0
 */
class MemberController
{
    /**
     * 会员列表
     */
    public function getIndex(Request $request)
    {
        $p = $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $condition = [];

        //搜索昵称或登录名
        $name = $request->get('nickname');
        if ($name) {
            $condition[] = "nickname like '%" . trim($name) . "%'";
        }

        //搜索手机号
        $mobile = $request->get('mobile');
        if($mobile){
            $condition[] = "mobile = ". $mobile;
        }

        $list = MemberService::findMemberList($page, $condition);
        return View::render('@AppBundle/member/index.twig', [
            'list' => $list,
             'page' => $page
        ]);
    }

    /**
     * 会员基本信息
     */
    public function getView(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $member = MemberService::findMember($id);
        if($member == null){
            $app->abort(404, "会员id错误");
        }

        return View::render('@AppBundle/member/view.twig', [
            'member' => $member
        ]);
    }

    /**
     * 下级
     */
    public function getChildren(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $p = $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $member = MemberService::findMember($id);
        if($member == null){
            $app->abort(404, "会员id错误");
        }

        $list = MemberService::findAllChildren( $page, $id);
        return View::render('@AppBundle/member/children.twig', [
            'list' => $list,
            'page' => $page,
            'member' => $member
        ]);
    }

    /**
     * 资金明细
     */
    public function getMoney(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $p = $request->get('page', 1);
        $page = Pagination::createFromCurrentNumber($p);
        $member = MemberService::findMember($id);
        if($member == null){
            $app->abort(404, "会员id错误");
        }

        $list = MemberService::findMemberCapitalDetails( $page, $id);
        return View::render('@AppBundle/member/money.twig', [
            'list' => $list,
            'page' => $page,
            'member' => $member
        ]);
    }

    /**
     * 修改会员信息
     */
//    public function anyUpdate(Request $request, Application $app)
//    {
//        $errors = [];
//        $id = $request->get('id', 0);
//        $member = MemberService::findMember($id);
//        if($member == null){
//            $app->abort(404, "会员id错误");
//        }
//
//        if($request->isMethod('post')) {
//            $data = $request->get('Member');
//            if(MemberService::updateMember($id, $data, $errors)) {
//                Session::setFlash("message", "修改成功");
//                return Redirect::to('member/index');
//            }
//        }
//
//        return View::render('@AppBundle/member/update.twig', [
//            'member' => $member,
//            'errors' => $errors,
//            'statusArr' => Member::statusParams(),
//            'gradeArr' => Member::gradeParams()
//
//        ]);
//    }

    /**
     * 导入导出也
     */
//    public function getExportAndImport()
//    {
//         return View::render('@AppBundle/member/export.twig');
//    }

    /**
     * ajax上传文件
     */
    /*public function anyAjaxUpload()
    {
        $up = new UploadedFile();
        $up->basePath = 'uploads/excel/';
        $up->subPath = @date('Ym') . '/' . @date('d') . '/';
         $up->extensionName =  array('xls', 'xlsx');
        if ($up->doUpload()) {
            $result = array(
                'status' => 1,
                'files' => $up->getFiles()
            );
            Session::put('url', $up->getFiles()[0]['basePath'] . $up->getFiles()[0]['subPath'] . $up->getFiles()[0]['basename']);

        } else {
            $result = array(
                'status' => 0,
                'files' => $up->getError()
            );
        }
        return json_encode($result);

    }*/

    /**
     * 下载模版
     */
//    public function getDownFile()
//    {
//        $filename=realpath("./file/member.xls"); //文件名
//
//        //弹出下载对话框
//        header('Content-Type: application/octet-stream');
//        header('Content-Disposition: attachment; filename=会员导入模版.xls');
//        header('Content-Length: ' . filesize($filename));
//
//        readfile($filename);
//        exit;
//
//    }

    /**
     * 会员信息导出
     */
//    public function getExport()
//    {
//        $map = [
//            'username' => '手机号',
//            'nickname' => '昵称',
//            'email' => '邮箱',
//            'status' => '状态(启用/冻结)',
//            'grade' => '等级(普通会员/VIP)',
//            'point' => '积分',
//            'money' => '账户余额',
//            'total_fee' => '累计消费总金额',
//            'end_timestamp' => '会员到期时间',
//        ];
//        $filename = date("Y-m-d", time())."康骏会员数据";
//        $excel = new \PHPExcel\Excel(\Rain\Application::$app['path'] . '/runtime/');
//        $excel->exportExcel(MemberService::findAllMemberForExport(), $map, $filename);
//
//    }

    /**
     * 会员信息导入
     */
//    public function anyImport()
//    {
//        $result = [
//            'status' => 0,
//            'message' => ''
//        ];
//
//        //获取文件名
//        $url = Session::pull("url");
//        if(!isset($url)) {
//            $result['message'] = "请先上传文件";
//            return json_encode($result);
//        }
//
//        $filename = dirname($_SERVER['SCRIPT_FILENAME']). '/' . $url;
//        chmod($filename,0755);
//
//        $map = [
//            'username' => '手机号',
//            'nickname' => '昵称',
//            'email' => '邮箱',
//            'status' => '状态(启用/冻结)',
//            'grade' => '等级(普通会员/VIP)',
//            'point' => '积分',
//            'money' => '账户余额',
//            'total_fee' => '累计消费总金额',
//            'end_timestamp' => '会员到期时间'
//        ];
//
//        $excel = new \PHPExcel\Excel();
//        $data = $excel->readExcelFile($filename, $map);
//
//        $error = '';
//        if(MemberService::addMember($data, $error)) {
//            $result['message'] = $error;
//            $result['status'] = 1;
//        } else {
//            $result['message'] = "导入会员信息失败";
//        }
//        return json_encode($result);
//
//    }


    /**
     * 会员卡券
     */
//    public function getTicket(Request $request, Application $app)
//    {
//        $memberId = $request->get("id", 0);
//        $member = MemberService::findMember($memberId);
//        if($member == null) {
//            $app->abort("404", "会员id错误");
//        }
//
//        $p = $request->get('page', 1);
//        $page = Pagination::createFromCurrentNumber($p);
//        $tickets = MemberService::findAllTicket($page, $memberId);
//        return View::render('@AppBundle/member/ticket-list.twig', [
//            'list' => $tickets,
//            'page' => $page,
//            'member' => $member
//        ]);
//
//    }

    /**
     * 会员重置密码
     */
    public function anyReset(Request $request, Application $app)
    {
        $id = $request->get('id', 0);
        $member = MemberService::findMember($id);

        if($request->isMethod('get')) {
            if($member == null) {
                $app->abort(404, "会员ID错误");
            }

            return View::render('@AppBundle/member/reset.twig', [
                'member' => $member
            ]);
        }


        $newPassword = $request->get("password", 0);

        $result = [
            'status' => 0,
            'message' => ''
        ];
        $error = '';
        if(MemberService::updateMemberPassword($member->username, $newPassword, $error)){
            $result['status'] = 1;
        }
        $result['message'] = $error;
        return json_encode($result);

    }

}