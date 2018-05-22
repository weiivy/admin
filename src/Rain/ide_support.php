<?php

namespace Rain {

    class Url extends \Rain\Facade\UrlFacade
    {
    }

    class Cache extends \Rain\Facade\CacheFacade
    {
    }

    class Mail extends \Rain\Facade\MailFacade
    {
    }

    class Request extends \Symfony\Component\HttpFoundation\Request
    {
    }

    class Auth extends \Rain\Auth\AuthManager
    {
    }

    /**
     * 数据库操作帮助类
     *
     * @author  Zou Yiliang
     */
    class DB
    {
        /**
         * @return \Rain\Database\DBConnection
         */
        public static function getConnection()
        {
        }

        /**
         * 返回数据库查询对象
         * 此方法将自动添加表前缀, 例如配置的表前缀为"cms_", 则"user"将被替换为 "cms_user", 相当于"{{%user}}"
         * 如果希望使用后缀，使用'{{user%}}'
         * 如果不希望添加表前缀，使用'{{user}}'
         * 如果使用自定义表前缀，使用'{{wp_user}}'
         * @param string $tableName
         * @return \Rain\Database\DBQuery
         */
        public static function select($tableName = null)
        {
        }

        /**
         * 执行数据库INSERT操作
         * 执行成功后需要取自增ID, 使用DB::getConnection()->lastInsertId()
         * @param $tableName
         * @param array $data 关联数组
         * 例如['name' => 'Jack', 'age' => 20] 其中key为列名，value为值, 列名只允许字母数字或下划线
         * @return bool
         * @throws \Exception
         */
        public static function insert($tableName, array $data)
        {
        }

        /**
         * 执行数据库INSERT操作，并返回自增id，失败返回0
         * @param $tableName
         * @param array $data
         * @return int
         * @throws \Exception
         */
        public static function insertGetId($tableName, array $data)
        {
        }

        /**
         * 执行数据库UPDATE操作
         * @param string $tableName
         * @param array $data 列名只允许字母数字或下划线
         * @param string $condition
         * @param array $params
         * @return int
         * @throws \Exception
         */
        public static function update($tableName, $data, $condition, array $params = array())
        {
        }

        /**
         * 执行数据库DELETE操作
         * @param $tableName
         * @param string $condition
         * @param array $params
         * @return int
         * @throws \Exception
         */
        public static function delete($tableName, $condition = '', array $params = array())
        {
        }

    }
}