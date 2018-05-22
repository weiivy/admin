<?php
namespace WeixinBundle\Controller;

use Common\Service\ConsumptionService;
use InterfaceBundle\Entity\Member;
use InterfaceBundle\Service\EmployeeService;
use InterfaceBundle\Service\MemberService;
use InterfaceBundle\Service\ProductService;
use InterfaceBundle\Service\TicketService;
use Rain\Auth;
use Rain\Pagination;
use Rain\Redirect;
use Rain\Request;
use Rain\UploadedFile;
use Rain\View;

/**
 * 会员控制器
 *
 * @author Zhang Weiwei
 * @since1.0
 */
class MemberController
{
    /**
     * 技师服务项目 => 项目详情 => 添加地址
     */
    public function anyAjaxAddAddress(Request $request)
    {
        $data = $request->get("Address");
        $result = MemberService::createNewAddress($data);
        return json_encode($result);
    }

    /**
     * 用户中心
     */
    public function getIndex()
    {
        /*if(!Auth::isLogin()){
            return Redirect::to('default/login');
        }*/
        $member = Auth::getIdentity(Member::className());
        return View::render('@WeixinBundle/member/index.twig', [
            'member' => $member
        ]);
    }


    /**
     * 会员地址列表
     */
    public function getAddressList(Request $request)
    {
        $memberId = $request->get('id', 0);
        $member = Auth::getIdentity(Member::className());
        if($member == null && $memberId != $member->id){
            return Redirect::to('default/login');
        }

        $addressList = MemberService::findAddressList($member->id);
        return View::render('@WeixinBundle/member/address-list.twig', [
            'list' => $addressList,
            'memberId' => $member->id,
            'member' => $member
        ]);
    }

    /**
     * 编辑地址
     */
    public function getUpdateAddress(Request $request)
    {
        $memberId = $request->get("memberId", 0);
        $addressId = $request->get("addressId", 0);
        $address = MemberService::findAddressInfo($memberId, $addressId);
        return View::render('@WeixinBundle/member/update-address.twig', [
            'address' => $address,
            'memberId' => $memberId,
            'addressId' => $addressId,
        ]);
    }

    /**
     * 编辑地址
     */
    public function anyAjaxEditAddress(Request $request)
    {
        $data = $request->get('address');
        $result = MemberService::updateAddress($data);
        return json_encode($result);
    }

    /**
     * 编辑地址 => 删除地址
     */
    public function anyAjaxDeleteAddress(Request $request)
    {
        $data = $request->get('address');
        $result = MemberService::deleteAddress($data);
         return json_encode($result);
    }

    /**
     * 个人信息
     */
    public function getMemberInfo(Request $request)
    {
         $memberId = $request->get('id', 0);
         $member = Auth::getIdentity(Member::className());
         if($member == null && $memberId != $member->id){
             return Redirect::to('default/login');
         }

        return View::render('@WeixinBundle/member/member-info.twig', [
            'list' => $member
        ]);
    }

    /**
     * 修改头像
     */
    public function anyAjaxUpload()
    {
        $up = new UploadedFile();
        $up->thumb = Member::getThumbParams();
        $up->basePath = Member::getImagePath() . '/';
        $up->subPath = @date('Ym') . '/' . @date('d') . '/';
        if ($up->doUpload()) {
            $data['avatar'] = $up->getFiles()[0]['subPath'] . $up->getFiles()[0]['basename'];
            $member = Auth::getIdentity(Member::className());
            $bool = MemberService::updateMemberAvatar($member->id, $data);
            $result = array(
                'status' => (int)$bool,
                'files' => $up->getFiles()
            );
        } else {
            $result = array(
                'status' => 0,
                'files' => $up->getError()
            );
        }
        return json_encode($result);

    }

    /**
     * ajax 修改昵称
     */
    public function anyAjaxChangeNickname(Request $request)
    {
        $data['nickname'] = $request->get('nickname', null);
        $id = $request->get('id', 0);
        $result = MemberService::updateMemberNickname($id, $data);
        return json_encode($result);
    }

    /**
     * 我的收藏 => 服务收藏列表
     */
    public function getCollectProduct()
    {
        $member = Auth::getIdentity(Member::className());

        $product = ProductService::findCollectByMemberId($member->id);
        return View::render('@WeixinBundle/member/collect-product.twig', [
            'product' => $product
        ]);
    }

    /**
     * 我的收藏 => 技师收藏列表
     */
    public function getCollectEmployee()
    {
        $member = Auth::getIdentity(Member::className());

        $employee = EmployeeService::findCollectByMemberId($member->id);
        return View::render('@WeixinBundle/member/collect-employee.twig', [
            'employee' => $employee
        ]);
    }

    /**
     * 我的卡券
     */
    public function getTicket()
    {
        $member = Auth::getIdentity(Member::className());

        $tickets = TicketService::findAllTicket($member->id);
        return View::render('@WeixinBundle/member/ticket-list.twig', [
            'tickets' => $tickets
        ]);
    }

    /**
     * 账号中心
     */
    public function getAccount (Request $request)
    {
        $member = Auth::getIdentity(Member::className());
        if($request->isMethod("get")) {
            return View::render('@WeixinBundle/member/account.twig',[
                'member' => $member
            ]);
        }
    }

    /**
     * 会员消费明细
     */
    public function anyBalance(Request $request)
    {
        if($request->isMethod("get")) {
            return View::render('@WeixinBundle/member/balance.twig');
        }
        $member = Auth::getIdentity(Member::className());
        $p = $request->get('currentPage', 1);
        $page = Pagination::createFromCurrentNumber($p);

        $list = ConsumptionService::findAllConsumptionByMemberId($page, $member->id);
        $result = [
            'status' => 1,
            'data' => $list
        ];
        return json_encode($result);


    }

    /**
     * 会员重置密码
     */
   /* public function anyReset(Request $request)
    {
        $id = $request->get('id', 0);
        $member = MemberService::findByPkFromMember($id);
        if($request->isMethod('get')) {
            return View::render('@WeixinBundle/member/reset.twig',[
                'member' => $member
            ]);
        }
        $oldPassword = $request->get("oldPassword", 0);
        $newPassword = $request->get("password", 0);

        $result = [
            'status' => 0,
            'message' => ''
        ];
        $error = '';
        if(MemberService::updateMemberPassword($member->username,$oldPassword, $newPassword, $error)){
            $result['status'] = 1;
        }
        $result['message'] = $error;
        return json_encode($result);

    }*/

}