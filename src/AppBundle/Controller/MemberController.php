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

        $grade = $request->get('grade');
        if ($grade) {
            $condition[] = "grade = ". $grade;;
        }

        //搜索手机号
        $mobile = $request->get('mobile');
        if($mobile){
            $condition[] = "mobile = ". $mobile;
        }

        $list = MemberService::findMemberList($page, $condition);
        return View::render('@AppBundle/member/index.twig', [
            'list' => $list,
             'gradeList' => Member::gradeParams(),
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
     * 更新会员等级
     */
    public function anyAjaxUpdateGrade(Request $request, Application $app)
    {
        $memberId = $request->get('mid', 0);
        $grade = $request->get('grade', Member::GRADE_10);
        $member = MemberService::findMember($memberId);
        if($member == null) {
            $app->abort(404, "会员ID错误");
        }
        $grades = array_keys(Member::gradeParams());
        if(!in_array($grade, $grades)) {
            $app->abort(404, "当前选择的会员等级不存在");
        }

        if(MemberService::updateGrade($memberId, ['grade' => $grade])) {
            return json_encode(['status' => 1, 'message' => "更新成功"]);
        }
        return json_encode(['status' => 0, 'message' => "更新失败"]);

    }
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