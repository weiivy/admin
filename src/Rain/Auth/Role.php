<?php
namespace Rain\Auth;

use Rain\DB;
use Rain\Object;

class Role extends Object implements IdentityInterface
{
    public static function tableName()
    {
        return '{{%role}}';
    }

    /**
     * 返回Role ID
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 根据ID返回Role 对象
     * @param int|string $id
     * @return IdentityInterface|null
     */
    public static function findIdentity($id)
    {
        $role = DB::select(static::tableName())->asEntity(static::className())->findByPk($id);
        if ($role === null) {
            throw new \RuntimeException('No Role found for id ' . $id);
        }
        return $role;
    }
}