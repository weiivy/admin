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
     * 获取所有导出会员信息
     * @return array
     */
    /*public static function findAllMemberForExport()
    {
        $members = DB::select(Member::tableName())->asEntity(Member::className())->findAll();
        $data = [];
        foreach($members as $key => $member) {
            $data[$key]['username'] = empty($member->username) ? '-' : $member->username;
            $data[$key]['nickname'] = empty($member->nickname) ? '-' : $member->nickname;
            $data[$key]['email'] = empty($member->emial) ? '-' : $member->emial;
            $data[$key]['status'] = Member::statusParams()[$member->status];
            $data[$key]['grade'] = Member::gradeParams()[$member->grade];
            $data[$key]['point'] = $member->point;
            $data[$key]['money'] = $member->money;
            $data[$key]['total_fee'] = $member->total_fee;
            $data[$key]['end_timestamp'] = date('Y/m/d', $member->end_timestamp);
        }

        return $data;
    }*/

    /**
     * 添加用户
     * @param $data
     * @param string $error
     * @return bool
     */
    /*public static function addMember($data, &$error = '')
    {


        $successCount = 0;
        $existsCount = 0;
        $grades = array_flip(Member::gradeParams());
        DB::getConnection()->beginTransaction();
        foreach(array_filter($data) as $val) {
            //账号不能为空
            if(empty($val['username'])) {
                continue;
            }
            $temp = [];
            $temp['mobile'] = $temp['username'] = $val['username'];
            $temp['nickname'] = $val['nickname'];
            $temp['password_hash'] = static::makePasswordHash('jundaojia');
            $temp['email'] = $val['email'];
            $temp['point'] = (int)$val['point'];
            $temp['money'] = (float)$val['money'];
            $temp['total_fee'] = (float)$val['total_fee'];
            if($val['status'] == "启用") {
                $temp['status'] = Member::STATUS_ON;
            } else if($val['status'] == "冻结") {
                $temp['status'] = Member::STATUS_OFF;
            }

            $temp['grade'] = $grades[$val['grade']];

            $temp['end_timestamp'] = $val['end_timestamp'] == 0 ? 0 : strtotime(date('Y-m-d 23:59:59', intval(($val['end_timestamp']) - 25569) * 3600 * 24));

            //验证有无记录
            $member = DB::select(Member::tableName())->asEntity(Member::className())->find('username =?', [$temp['username']]);
            if($member != null) {
                $existsCount++;
                continue;
            }

            $temp['created_at'] = $temp['updated_at'] = time();
            if(!DB::insert(Member::tableName(), $temp)) {
                Log::error("导入会员信息失败.\n". DB::getLastSql());
                DB::getConnection()->rollBack();
                return false;
            }

            $successCount++;
        }

        DB::getConnection()->commit();
        $error = "成功导入". $successCount . "人会员信息，已存在会员" . $existsCount . "人";
        return true;
    }*/


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
     */
    public static function updateGrade($memberId)
    {
        $data['grade'] = 0;
        $data['end_timestamp'] = 0;
        $data['updated_at'] = time();
        if(!DB::update(Member::tableName(), $data, 'id = ?', [$memberId]) == 1){
           Log::error("会员到期修改会员为普通等级失败\n" . DB::getLastSql());
        }
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