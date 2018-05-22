<?php

namespace Rain\Database;

/**
 * 查询构造器
 * @author  Zou Yiliang
 */
class DBQuery
{
    protected $db;

    protected $table;
    protected $orderBy;
    protected $limit;
    protected $offset;
    protected $condition;
    protected $params = array();
    protected $entity;
    protected $lockForUpdate;
    protected $sharedLock;

    public function __construct($table, $db)
    {
        $this->table = $table;
        $this->db = $db;
    }

    /**
     * @return DBConnection
     */
    protected function getDbConnection()
    {
        return $this->db;
    }

    /**
     * 根所SQL查询，返回符合条件的所有数据
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function findAllBySql($sql = '', $params = array())
    {
        if ($this->entity === null) {
            return self::getDbConnection()->query($sql, $params, \PDO::FETCH_ASSOC);
        } else {
            return self::getDbConnection()->query($sql, $params, \PDO::FETCH_CLASS, $this->entity);
        }
    }

    /**
     *
     * 返回符合条件的所有数据
     *
     * @param string $condition
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function findAll($condition = '', $params = array())
    {
        if (!DBQueryBuilder::isEmpty($condition)) {
            $this->where($condition, $params);
        }

        $sql = $this->getSelectSql();
        $params = $this->params;

        $this->reset();

        return self::findAllBySql($sql, $params);
    }

    /**
     * @param string $field
     * @param string $condition
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function count($field = '*', $condition = '', $params = array())
    {
        if (!preg_match('/^[\w\.]+|\*$/', $field)) {
            throw new \Exception('field error.');
        }

        if (!DBQueryBuilder::isEmpty($condition)) {
            $this->where($condition, $params);
        }

        $tableName = DBQueryBuilder::addTablePrefix($this->table);

        $arr = array();
        $arr['select'] = "SELECT count({$field}) FROM " . $tableName;
        $arr['where'] = DBQueryBuilder::isEmpty($this->condition) ? '' : "WHERE {$this->condition}";

        $sql = join(' ', $arr);

        $params = $this->params;

        $this->reset();

        return (int)self::getDbConnection()->queryScalar($sql, $params);

    }

    /**
     * @param int|string $limit
     * @return $this
     */
    public function limit($limit)
    {
        //e.g. "20,10"
        if (is_string($limit) && strpos($limit, ',') !== false) {
            list($this->offset, $limit) = explode(',', $limit);
        }

        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param array|string $columns
     * @return $this
     */
    public function orderBy($columns)
    {
        $this->orderBy = $this->normalizeOrderBy($columns);
        return $this;
    }

    /**
     * and where
     * @param string $condition
     * @param array $params
     * @return $this
     */
    public function where($condition = '', $params = array())
    {
        if (empty($this->condition)) {
            $this->condition = $condition;
            $this->params = $params;
        } else {
            $this->condition = "($this->condition) AND ($condition)";
            $this->params = array_merge($this->params, $params);
        }
        return $this;
    }

    /**
     * and where in
     * @param string $field 字段
     * @param array $values 索引数组
     * @return $this
     * @throws \Exception
     */
    public function whereIn($field, array $values)
    {
        static $count = 0;

        if (count($values) > 0) {
            if (preg_match('/^\w+$/', $field)) {

                $values = array_values($values);

                $temp = array();
                $params = array();
                for ($i = 0; $i < count($values); $i++) {

                    $key = DBQueryBuilder::PARAM_PREFIX . '_in_' . ($i + $count);
                    $temp[] = $key;
                    $params[$key] = $values[$i];
                }

                $this->where("`$field` IN (" . implode(', ', $temp) . ')', $params);
                return $this;
            }
        }
        throw new \Exception('where in error');
    }

    /**
     *
     * @param $className
     * @return $this
     */
    public function asEntity($className)
    {
        $this->entity = $className;
        return $this;
    }

    public function lockForUpdate()
    {
        $this->lockForUpdate = true;
        return $this;
    }

    public function sharedLock()
    {
        $this->sharedLock = true;
        return $this;
    }

    /**
     *
     * 返回符合条件的单条数据
     *
     * @param string $condition
     * @param array $params
     * @return array | null
     */
    public function find($condition = '', $params = array())
    {
        $this->limit = 1;

        $arr = $this->findAll($condition, $params);
        if (count($arr) === 0) {
            return null;
        }

        return $arr[0];
    }

    /**
     * 根据主键返回对象
     * @param $pk
     * @param string $condition
     * @param array $params
     * @return  array | null
     */
    public function findByPk($pk, $condition = '', $params = array())
    {
        $primaryKey = 'id';

        $this->limit = 1;

        $this->where("`$primaryKey` = ?", array($pk));

        return $this->find($condition, $params);
    }

    /**
     * 根据SQL返回对象
     * @param string $sql
     * @param array $params
     * @return array | null
     */
    public function findBySql($sql = '', $params = array())
    {
        $arr = self::findAllBySql($sql, $params);
        if (count($arr) > 0) {
            return $arr[0];
        }
        return null;
    }

    /**
     * 拆分查询，用于处理非常多(数千条)的查询结果,而不会消耗大量内存，建议加上排序字段
     * @param int $num 每次取出的数据数量 例如 100
     * @param callback $callback 每次取出数据时被调用,传入每次查询得到的数据(数组)
     *
     * DB::select('user')->where('status=1')->orderBy('id')->chunk(100, function ($users) {
     *    foreach ($users as $user) {
     *      // ...
     *    }
     * });
     *
     */
    public function chunk($num, $callback)
    {
        $offset = 0;
        $limit = (int)$num;
        do {
            $query = clone $this;
            $query->offset($offset);
            $query->limit($limit);
            $data = $query->findAll();
            $offset += $limit;
            call_user_func($callback, $data);
            unset($query);
        } while (count($data) === $limit);
        $this->reset();
    }

    /**
     * Normalizes format of ORDER BY data
     *
     * @param array|string $columns
     * @return array
     */
    protected function normalizeOrderBy($columns)
    {
        if (is_array($columns)) {
            return $columns;
        } else {

            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);

            $result = [];
            foreach ($columns as $column) {
                if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                    $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
                } else {
                    $result[$column] = SORT_ASC;
                }
            }

            return $result;
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getOrderByString()
    {
        // 排序
        if ($this->orderBy !== null) {

            $columns = $this->orderBy;
            $orders = [];
            foreach ($columns as $name => $direction) {
                //列名只允许字母、数字、下划线、点(.)、中杠(-)
                if (!preg_match('/^[\w\-\.]+$/', $name)) {
                    throw new \Exception('Order by field error: "' . htmlspecialchars($name) . '"');
                }

                $orders[] = $name . ($direction === SORT_DESC ? ' DESC' : '');
            }

            return 'ORDER BY ' . implode(', ', $orders);
        }
        return '';
    }

    /**
     * @return string
     */
    protected function getLimitString()
    {
        if (!DBQueryBuilder::isEmpty($this->limit)) {
            $limit = (int)$this->limit;
            $offset = (int)$this->offset;
            return "LIMIT {$offset}, {$limit}";
        }
        return '';
    }

    /**
     * 构造查询SQL
     * @return string
     * @throws \Exception
     */
    protected function getSelectSql()
    {
        if (empty($this->table)) {
            throw new \Exception('table name is empty.');
        }

        $tableName = DBQueryBuilder::addTablePrefix($this->table);

        $arr = array();
        $arr['select'] = 'SELECT * FROM ' . $tableName;
        $arr['where'] = DBQueryBuilder::isEmpty($this->condition) ? '' : "WHERE {$this->condition}";
        $arr['orderBy'] = $this->getOrderByString();
        $arr['limit'] = $this->getLimitString();

        $sql = join(' ', $arr);

        //如果同时存在问号和冒号，则将问号参数转为冒号
        if (strpos($sql, '?') !== false && strpos($sql, ':') !== false) {
            $count = substr_count($sql, '?');
            for ($i = 0; $i < $count; $i++) {
                $sql = preg_replace('/\?/', DBQueryBuilder::PARAM_PREFIX . $i, $sql, 1);
                $this->params[DBQueryBuilder::PARAM_PREFIX . $i] = $this->params[$i];
                unset($this->params[$i]);
            }
        }

        if ($this->lockForUpdate === true) {
            $sql = trim($sql) . ' FOR UPDATE';
        }

        if ($this->sharedLock === true) {
            $sql = trim($sql) . ' LOCK IN SHARE MODE';
        }

        return $sql;
    }

    /**
     * 清空所有查询条件
     */
    protected function reset()
    {
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->condition = null;
        $this->params = array();
        $this->lockForUpdate = null;
        $this->sharedLock = null;
    }
}