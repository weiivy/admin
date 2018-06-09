<?php
namespace AppBundle\Service;


use AppBundle\Entity\BankConfig;
use Rain\DB;
use Rain\Log;
use Rain\Pagination;
use Rain\Validator;

class BankConfigService
{
    /**
     * 银行兑换比例列表（带分页）
     * @param Pagination $page
     * @param array $condition
     * @param array $params
     * @param string $order
     * @return array
     */
    public static function findBankConfigList(Pagination $page, $condition = [], $params = [], $order = 'bank_id desc')
    {
        $condition[] = 'status = :status';
        $params[':status'] = BankConfig::STATUS_NORMAL;
        $query = DB::select(BankConfig::tableName())->where(implode(' and ', $condition), $params);
        $queryCount = clone $query;
        $page->itemCount = $queryCount->count();

        return $query->asEntity(BankConfig::className())
            ->limit($page->limit)
            ->orderBy($order)
            ->findAll();
    }

    /**
     * 根据id返回会员模型
     * @param $id
     * @return array|null
     */
    public static function findBankConfig($id)
    {
        return DB::select(BankConfig::tableName())->where('status =:status', [':status' => BankConfig::STATUS_NORMAL])->asEntity(BankConfig::className())->findByPk($id);
    }

    /**
     * 新增银行兑换比例
     * @param $data
     * @param array $errors
     * @return bool
     */
    public static function addBankConfig($data, &$errors = [])
    {
        $rule = [
            [['bank_id', 'type','money', 'score'], 'required'],
            [['score', 'status', 'type', 'bank_id'], 'integer'],
            [['money'], 'double'],
        ];

        if(!Validator::validate($data, $rule)) {
            $errors = Validator::getFirstErrors();
            return false;
        }

        $data['created_at'] = $data['updated_at'] = time();
        if(DB::insert(BankConfig::tableName(), $data)) {
            return true;
        } else {
            Log::error("新增银行兑换比例失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

    /**
     * 修改银行兑换比例
     * @param $id
     * @param $data
     * @param array $errors
     * @return bool
     */
    public static function updateBankConfig($id, $data, &$errors = [])
    {
        $rule = [
            [['bank_id', 'type','money', 'score'], 'required'],
            [['score', 'status', 'type', 'bank_id'], 'integer'],
            [['money'], 'double'],
        ];

        if(!Validator::validate($data, $rule)) {
            $errors = Validator::getFirstErrors();
            return false;
        }

        $ad = static::findBankConfig($id);

        $data['updated_at'] = time();
        if(DB::update(BankConfig::tableName(), $data, 'id = ?', [$id]) == 1) {
            return true;
        } else {
            Log::error("修改银行兑换比例失败\n". DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }

    /**
     * 软删除银行兑换比例
     * @param BankConfig $bankConfig
     * @param array $errors
     * @return bool
     */
    public static function deleteBankConfig(BankConfig $bankConfig, &$errors = [])
    {
        $data['status'] = BankConfig::STATUS_DELETE;
        $data['updated_at'] = time();
        if(DB::update(BankConfig::tableName(), $data, 'id = ?', [$bankConfig->id])) {
            return true;
        } else {
            Log::error("删除银行兑换比例.\n" . DB::getLastSql());
            $errors[] = "服务器错误";
            return false;
        }
    }
}