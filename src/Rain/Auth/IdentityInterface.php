<?php

namespace Rain\Auth;

/**
 * 用户认证接口
 */
interface IdentityInterface
{
    /**
     * 返回用户ID
     * @return int|string
     */
    public function getId();

    /**
     * 根据ID返回用户对象
     * @param int|string $id
     * @return IdentityInterface|null
     */
    public static function findIdentity($id);

}