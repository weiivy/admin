<?php
namespace AppBundle\Service;


use Rain\Auth;
use User;
use Rain\DB;
use Rain\Log;
use Rain\Validator;

/**
 * 用户服务类
 * @author  Zou Yiliang
 * @since   1.0
 */
class UserService
{
    /**
     * 尝试通过用户名和密码登录，成功返回对象，失败返回null
     * @param $username
     * @param $password
     * @return bool
     */
    public static function loginByNameAndPassword($username, $password, &$errorMessage = null)
    {
        if (strlen(trim($username)) === 0) {
            return false;
        }

        $user = DB::select(User::tableName())
            ->asEntity(User::className())
            ->find('username = ?', [$username]);

        if ($user === null || $user->password_hash !== static::makePasswordHash($password)) {
            $errorMessage = '用户不存在或密码不匹配';
            return false;
        }

        if ($user->status != User::STATUS_ALLOW) {
            $errorMessage = '此帐户不允许登录';
            return false;
        }

        Auth::login($user);
        static::afterLogin($user);
        return true;
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
     * 成功登录后执行操作
     * @param User $user
     */
    protected static function afterLogin(User $user)
    {
        //更新登录时间
        DB::update(User::tableName(), array('login_at' => time()), 'id = ?', array($user->id));
    }

    /**
     * 注册用户
     * @param array $data
     * @param string $errorMessage
     * @return bool
     */
    public static function register(array $data, &$errorMessage = null)
    {
        $rule = [
            [['username', 'password', 'email'], 'required'],
            [['username', 'email'], 'email'],
            [['password'], 'string', 'length' => [4, 20]],
        ];

        //验证输入是否合法
        if (!Validator::validate($data, $rule)) {
            $errorMessage = Validator::getFirstError();
            return false;
        }

        $data['status'] = User::STATUS_ALLOW;

        DB::getConnection()->beginTransaction();

        //用户名是否已存在
        $sql = 'SELECT COUNT(*) FROM ' . User::tableName() . ' WHERE username = ? FOR UPDATE';
        $count = DB::getConnection()->queryScalar($sql, [$data['username']]);
        if ($count > 0) {
            $errorMessage = '用户已存在';
            DB::getConnection()->rollBack();
            return false;
        }

        //生成密码hash
        $data['password_hash'] = static::makePasswordHash($data['password']);
        unset($data['password']);

        //保存数据库
        if (DB::insert(User::tableName(), $data)) {

            DB::getConnection()->commit();
            return true;

        } else {

            DB::getConnection()->rollBack();

            Log::error('注册用户数据库写入失败');
            Log::error($data);

            $errorMessage = '服务器错误';
            return false;
        }
    }
}