<?php

namespace Rain\Database;

/**
 * 数据库操作帮助类
 *
 * @author  Zou Yiliang
 */
class DBHelp
{
    protected $_db;

    public function __construct(DBConnection $conn)
    {
        $this->_db = $conn;
    }

    /**
     * @return DBConnection
     * @throws \Exception
     */
    public function getConnection()
    {
        if ($this->_db === null) {
            throw new \Exception('DBConnection is null');
        }
        return $this->_db;
    }

    /**
     * @param DBConnection $conn
     */
    public function setConnection(DBConnection $conn)
    {
        $this->_db = $conn;
    }

    /**
     * 返回数据库查询对象
     * 此方法将自动添加表前缀, 例如配置的表前缀为"cms_", 则"user"将被替换为 "cms_user", 相当于"{{%user}}"
     * 如果希望使用后缀，使用'{{user%}}'
     * 如果不希望添加表前缀，使用'{{user}}'
     * 如果使用自定义表前缀，使用'{{wp_user}}'
     * @param string $tableName
     * @return DBQuery
     */
    public function select($tableName = null)
    {
        return $query = new DBQuery($tableName, self::getConnection());
    }

    /**
     * 执行数据库INSERT操作
     * 执行成功后需要取自增ID, 使用insertGetId方法或 执行DB::getConnection()->lastInsertId()
     * @param $tableName
     * @param array $data 关联数组
     * 例如['name' => 'Jack', 'age' => 20] 其中key为列名，value为值, 列名只允许字母数字或下划线
     * @return bool
     * @throws \Exception
     */
    public function insert($tableName, array $data)
    {
        return self::getConnection()->execute(
            DBQueryBuilder::insert($tableName, $data),
            $data
        ) > 0;
    }

    /**
     * 执行数据库INSERT操作，并返回自增id，失败返回0
     * @param $tableName
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function insertGetId($tableName, array $data)
    {
        if ($this->insert($tableName, $data)) {
            return (int)$this->getConnection()->getLastInsertId();
        }
        return 0;
    }

    /**
     * 执行数据库UPDATE操作，返回受影响行数
     * @param string $tableName
     * @param array $data 列名只允许字母数字或下划线
     * @param string $condition
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function update($tableName, $data, $condition, array $params = array())
    {
        return self::getConnection()->execute(
            DBQueryBuilder::update($tableName, $data, $condition, $params),
            $params
        );
    }

    /**
     * 执行数据库DELETE操作，返回受影响行数
     * @param $tableName
     * @param string $condition
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function delete($tableName, $condition = '', array $params = array())
    {
        return self::getConnection()->execute(
            DBQueryBuilder::delete($tableName, $condition),
            $params
        );
    }

}