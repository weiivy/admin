<?php
namespace AppBundle\Service;
use AppBundle\Entity\Bank;
use Rain\DB;
use Rain\Log;
use Rain\Pagination;
use Rain\Validator;

/**
 * 银行服务类
 * @copyright (c) 2018, lulutrip.com
 * @author  Ivy Zhang<ivyzhang@lulutrip.com>
 */
class BankService
{
    /**
     * 带分页的银行列表
     * @param Pagination $page
     * @param string $order
     * @return array
     */
    public static function findAllBank(Pagination $page, $order = 'sort desc, id desc')
    {
        $query = DB::select(Bank::tableName())->where('status =:status', [':status' => Bank::STATUS_NORMAL]);
        $queryCount = clone $query;
        $page->itemCount = $queryCount->count();
        return $query->asEntity(Bank::className())->orderBy($order)->limit($page->limit)->findAll();
    }

    /**
     * 返回银行模型
     * @param $id
     * @return array|null
     */
    public static function findBank($id)
    {
        return DB::select(Bank::tableName())->where('status =:status', [':status' => Bank::STATUS_NORMAL])->asEntity(Bank::className())->findByPk($id);
    }

    /**
     * 新增银行
     * @param $data
     * @param array $errors
     * @return bool
     */
    public static function addBank($data, &$errors = [])
    {
        $rule = [
            [['bank', 'bank_name', 'note', 'sort'], 'required'],
            [['bank'], 'string', 'length' => [0, 20]],
            [['bank_name'], 'string', 'length' => [0, 100]],
            [['note'], 'string', 'length' => [0, 255]],
            [['sort'], 'integer'],
            [['status'], 'safe']
        ];

        if(!Validator::validate($data, $rule)) {
            $errors = Validator::getFirstErrors();
            return false;
        }

        $data['created_at'] = $data['updated_at'] = time();
        if(DB::insert(Bank::tableName(), $data)) {
            return true;
        } else {
            Log::error("新增银行失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

    /**
     * 修改银行
     * @param $id
     * @param $data
     * @param array $errors
     * @return bool
     */
    public static function updateBank($id, $data, &$errors = [])
    {
        $rule = [
            [['bank', 'bank_name', 'note', 'sort'], 'required'],
            [['bank'], 'string', 'length' => [0, 20]],
            [['bank_name'], 'string', 'length' => [0, 100]],
            [['note'], 'string', 'length' => [0, 255]],
            [['sort'], 'integer'],
            [['status'], 'safe']
        ];

        if(!Validator::validate($data, $rule)) {
            $errors = Validator::getFirstErrors();
            return false;
        }

        $bank = static::findBank($id);

        $data['updated_at'] = time();
        if(DB::update(Bank::tableName(), $data, 'id = ?', [$id]) == 1) {
            return true;
        } else {
            Log::error("修改银行失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

    /**
     * 软删除银行
     * @param Bank $bank
     * @param array $errors
     * @return bool
     */
    public static function deleteBank(Bank $bank, &$errors = [])
    {
        $data['status'] = Bank::STATUS_DELETE;
        $data['updated_at'] = time();
        if(DB::update(Bank::tableName(), $data, 'id = ?', [$bank->id])) {
            return true;
        } else {
            Log::error("删除银行.\n" . DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

    /**
     * 返回银行缩写与银行名称对应关系数组
     * @author Ivy Zhang<ivyzhang@lulutrip.com>
     * @return array 返回数据
     */
    public static function getBankKey()
    {
        $data = [];
        $banks =  DB::select(Bank::tableName())->asEntity(Bank::className())->where('status=:status', [':status' => Bank::STATUS_NORMAL])->findAll();
        if($banks === null) {
            return $data;
        }

        foreach ($banks as $bank){
            $data[$bank->id] = $bank->bank_name;
        }
        return $data;
    }
}