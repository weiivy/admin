<?php

namespace AppBundle\Service;

use AppBundle\Entity\CapitalDetails;
use AppBundle\Entity\Member;
use Common\Entity\Ticket;
use Common\Entity\TicketDetail;
use Rain\DB;
use Rain\Log;
use Rain\Pagination;
use Rain\Validator;

/**
 * 会员管理服务类
 *
 * @author Zhang Weiwei
 * @since 1.0
 */
class MemberService
{
    /**
     * 会员列表（带分页）
     * @param Pagination $page
     * @param array $condition
     * @param string $order
     * @return array
     */
     public static function findMemberList(Pagination $page, $condition = [], $order = 'id desc')
    {
        $query = DB::select(Member::tableName())->where(implode(' and ', $condition));
        $queryCount = clone $query;
        $page->itemCount = $queryCount->count();

        return $query->asEntity(Member::className())
            ->limit($page->limit)
            ->orderBy($order)
            ->findAll();
    }

    /**
     * 根据id返回会员模型
     * @param $id
     * @return array|null
     */
    public static function findMember($id)
    {
        return DB::select(Member::tableName())->asEntity(Member::className())->findByPk($id);
    }

    /**
     * 修改会员信息
     * @param $id
     * @param $data
     * @param array $errors
     * @return bool
     */
    public static function updateMember($id, $data, &$errors = [])
    {
        $rule = [
            [['mobile', 'status', 'grade'], 'required'],
            [['status', 'grade'], 'integer'],
            [['mobile'], 'string', 'length'=>[11,11]],
        ];

        if(!Validator::validate($data, $rule)) {
            $errors = Validator::getFirstErrors();
            return false;
        }

        $data['updated_at'] = time();
        if(DB::update(Member::tableName(), $data, 'id = ?', [$id])) {
            return true;
        } else {
            Log::error("修改会员信息失败\n". DB::getLastSql());
            $errors[] = "修改会员错误";
            return false;
        }
    }



    /**
     * 下级
     * @param Pagination $page
     * @param $memberId
     * @param string $order
     * @return array
     */
    public static function findAllChildren(Pagination $page, $memberId, $order = "id desc")
    {
        //获取总页码
        $query = DB::select(Member::tableName())->where('pid=:pid', [":pid" => $memberId]);
        $sqlCount = clone $query;
        $page->itemCount = $sqlCount->count();
        return $query->asEntity(Member::className())->orderBy($order)->limit($page->limit)->findAll();
    }

    /**
     * 资金明细
     * @param Pagination $page
     * @param $memberId
     * @param string $order
     * @return array
     */
    public static function findMemberCapitalDetails(Pagination $page, $memberId, $order = "id desc")
    {
        $query = DB::select(CapitalDetails::tableName())->where('member_id = :mid and status =:status', [':mid' => $memberId, ':status' => CapitalDetails::STATUS_1]);
        $sqlCount = clone $query;
        $page->itemCount = $sqlCount->count();
        return $query->asEntity(CapitalDetails::className())->orderBy($order)->limit($page->limit)->findAll();
    }



    /**
	 * 生成密码hash
	 * @param $password
	 * @return string
	 */
	protected static function makePasswordHash($password)
	{
		return md5($password);
	}


    /**
     * 通过会员账号及昵称查找会员
     * @param $username
     * @param $nickname
     * @return array|null
     */
    public static function findMemberByUsernameAndNickname($username, $nickname)
    {
        return DB::select(Member::tableName())
            ->asEntity(Member::className())
            ->find('username = ? or nickname = ?', [$username, $nickname]);
    }

    /**
     * 将会员改为普通会员
     * @param $memberId
     * @return bool
     */
    public static function updateGrade($id, $data)
    {
        $data['updated_at'] = time();
        if(!DB::update(Member::tableName(), $data, 'id = ?', [$id]) == 1){
           Log::error("会员等级失败\n" . DB::getLastSql());
           return false;
        }
        return true;
    }

    /**
     * 会员修改密码
     * @param $username
     * @param $newPassword
     * @param string $error
     * @return bool
     */
    public static function updateMemberPassword($username, $newPassword, &$error = '')
    {
        DB::getConnection()->beginTransaction();
        $member = DB::select(Member::tableName())->asEntity(Member::className())
            ->lockForUpdate()->find('username = ?', [$username]);


        $data['password_hash'] = static::makePasswordHash($newPassword);
        if($member->password_hash == $data['password_hash']) {
            DB::getConnection()->rollBack();
            $error = "新密码与原密码一致";
            return false;
        }

        $data['updated_at'] = time();
        if(DB::update(Member::tableName(), $data, 'username = ?', [$username]) == 1) {
            $error = '密码已修改';
            DB::getConnection()->commit();
            return true;
        } else {
            $error = "服务器错误";
            Log::error("修改密码失败\n". DB::getLastSql());
            DB::getConnection()->rollBack();
            return false;
        }

    }


    /**
     * 获取会员父级ID
     * @author Ivy Zhang<ivyzhang@lulutrip.com>
     * @copyright 2018-05-04
     * @param $memberId
     * @return mixed|null
     */
    public static function getPid($memberId)
    {
        $member = static::findMember($memberId);
        return empty($member) ? 0 : $member->pid;
    }


}