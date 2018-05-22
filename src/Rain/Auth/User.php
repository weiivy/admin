<?php
namespace Rain\Auth;

use Rain\DB;
use Rain\Object;

class User extends Object implements IdentityInterface
{
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * 返回用户ID
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 根据ID返回用户对象
     * @param int|string $id
     * @return IdentityInterface|null
     */
    public static function findIdentity($id)
    {
        $user = DB::select(static::tableName())->asEntity(static::className())->findByPk($id);
        if ($user === null) {
            throw new \RuntimeException('No user found for id ' . $id);
        }
        return $user;
    }
}