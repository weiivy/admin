<?php

namespace Rain\Database;

use \PDO;
use \Exception;

/**
 * 数据库操作
 * @author  Zou Yiliang
 * @since   1.0
 */
class DBConnection
{
    protected $pdo;
    protected $readPdo;
    protected $transactions = 0;

    protected $lastSql;
    protected $lastParams;

    protected $config = [
        'dsn' => 'mysql:host=localhost;dbname=test',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'tablePrefix' => '',
        'emulatePrepares' => false,
    ];

    protected $slaveConfig = [
        /*[
            'dsn' => 'mysql:host=localhost;dbname=test',
            'username' => 'root',
            'password' => '',
        ],*/
    ];

    public function __construct(array $config, $slave = array())
    {
        $this->config = array_replace_recursive($this->config, $config);
        $this->slaveConfig = $slave;
    }

    /**
     * 返回用于操作主库的PDO对象 (增、删、改)
     * @return PDO
     */
    protected function getPdo()
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $this->pdo = $this->makePdo($this->config);
        return $this->pdo;
    }

    protected function makePdo(array $config)
    {
        $pdo = new PDO($config['dsn'], $config['username'], $config['password']);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //false表示不使用PHP本地模拟prepare
        if (constant('PDO::ATTR_EMULATE_PREPARES')) {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $config['emulatePrepares']);
        }

        $pdo->exec('SET NAMES ' . $pdo->quote($config['charset']));

        return $pdo;
    }


    /**
     * 返回用于查询的PDO对象 (如果在事务中，将自动调用getPdo()以确保整个事务均使用主库)
     * @return PDO
     */
    protected function getReadPdo()
    {
        if ($this->transactions >= 1) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof PDO) {
            return $this->readPdo;
        }

        if (!is_array($this->slaveConfig) || count($this->slaveConfig) == 0) {
            return $this->getPdo();
        }

        $slaveDbConfig = $this->slaveConfig;
        shuffle($slaveDbConfig);
        do {
            // 取出一个打乱后的从库信息
            $config = array_shift($slaveDbConfig);

            // 使用主库信息补全从库配置
            $config = array_replace_recursive($this->config, $config);

            try {
                $this->readPdo = $this->makePdo($config);
                return $this->readPdo;
            } catch (\PDOException $ex) {
                //'slave database connect error. ' . $config['dsn']
            }

        } while (count($slaveDbConfig) > 0);

        // 没有可用的从库，直接使用主库
        return $this->readPdo = $this->getPdo();
    }

    /**
     * 返回表前缀
     * @return string
     */
    protected function getTablePrefix()
    {
        return $this->config['tablePrefix'];
    }

    /**
     * 解析SQL中的表名
     * 当表前缀为"cms_"时将sql中的"{{%user}}"解析为 "`cms_user`"
     * 解析"[[列名]]" 为 "`列名`"
     * @param $sql
     * @return string
     */
    public function quoteSql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return '`' . $matches[3] . '`';//quoteColumnName
                } else {
                    return str_replace('%', $this->getTablePrefix(), '`' . $matches[2] . '`');//quoteTableName
                }
            },
            $sql
        );
    }

    /**
     * @param string $sql 执行SQL语句，返回bool (增、删、改 类型的SQL), 执行成功返回受影响行数
     * PDOStatement::execute
     * PDOStatement::rowCount
     *
     * @param array $params
     * @return int
     * @throws Exception
     */
    public function execute($sql, $params = array())
    {
        $sql = $this->quoteSql($sql);
        $this->lastSql = $sql;
        $this->lastParams = $params;

        $statement = $this->getPdo()->prepare($sql);

        try {
            if ($statement->execute($params)) {
                return (int)$statement->rowCount();
            }
            return 0;
        } catch (\PDOException $ex) {
            throw new Exception($ex->getMessage() . "\n\n" . $this->getLastSql());
        }
    }

    /**
     * 执行SQL语句，返回array (查询类型的SQL)
     * PDOStatement::fetchAll
     *
     * @param $sql
     * @param array $params
     * @param int $fetchStyle 从此参数开始，为setFetchMode的参数，例如为PDO::FETCH_CLASS，则可以传入第4个参数
     * @return array
     * @throws Exception
     */
    public function query($sql, $params = array(), $fetchStyle = PDO::FETCH_ASSOC)
    {

        $sql = $this->quoteSql($sql);

        $this->lastSql = $sql;
        $this->lastParams = $params;

        try {
            $statement = $this->getReadPdo()->prepare($sql);
            $statement->execute($params);

            $args = func_get_args();
            $args = array_slice($args, 2);

            //PDOStatement::setFetchMode ( int $mode )
            //PDOStatement::setFetchMode ( int $PDO::FETCH_COLUMN , int $colno )
            //PDOStatement::setFetchMode ( int $PDO::FETCH_CLASS , string $classname , array $ctorargs )
            //PDOStatement::setFetchMode ( int $PDO::FETCH_INTO , object $object )
            call_user_func_array(array($statement, 'setFetchMode'), $args);
            return $statement->fetchAll();

        } catch (\PDOException $ex) {
            throw new Exception($ex->getMessage() . "\n\n" . $this->getLastSql());
        }
    }

    /**
     * 执行查询统计类型语句, 返回具体单个值, 常用于COUNT、AVG、MAX、MIN
     * @param $sql
     * @param array $params
     * @return mixed 成功返回数据，失败返回FALSE
     */
    public function queryScalar($sql, $params = array())
    {
        $sql = $this->quoteSql($sql);
        $statement = $this->getReadPdo()->prepare($sql);
        if ($statement->execute($params) && ($data = $statement->fetch(PDO::FETCH_NUM)) !== false) {
            if (is_array($data) && isset($data[0])) {
                return $data[0];
            }
        }
        return false;
    }

    /**
     * 返回最后插入行的ID或序列值
     * PDO::lastInsertId
     * @param null $sequence 序列名称
     * @return int|string
     */
    public function getLastInsertId($sequence = null)
    {
        $id = $this->getPdo()->lastInsertId($sequence);
        return is_numeric($id) ? (int)$id : $id;
    }

    /**
     * 开启事务
     * @return void
     */
    public function beginTransaction()
    {
        ++$this->transactions;
        if ($this->transactions == 1) {
            $this->getPdo()->beginTransaction();
        }
    }

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) $this->getPdo()->commit();
        --$this->transactions;
    }

    /**
     * 回滚事务
     *
     * @return void
     */
    public function rollBack()
    {
        if ($this->transactions == 1) {
            $this->transactions = 0;

            $this->getPdo()->rollBack();
        } else {
            --$this->transactions;
        }
    }

    public function getLastSql()
    {
        return $this->parseWhereString([$this->lastSql, $this->lastParams]);
    }

    //调试SQL使用
    //解析where中的"?"
    //参数中第一个成员为SQL，其它为占位符对应变量
    //parseWhereString('select * from tableName where id=? or id=?', array(1, 3));
    //parseWhereString('select * from tableName where name like :name' ,array(':name'=>'%jack%'));
    public function parseWhereString($where)
    {
        if (count($where) == 1) {
            return $where[0];
        }

        //  "id=?"
        $sql = array_shift($where);//将第一个弹出,剩下的是对应值
        // 检测有多少个问号
        $count = substr_count($sql, '?');

        if (is_array($where[0])) {
            $condition = $where[0];
        } else {
            $condition = $where;
        }

        if ($count > count($condition)) {
            // 参数不够
            throw new \Exception('parse sql error: ' . $sql . "\n" . print_r($condition, true));
        }

        // 一次替换一个问号
        for ($i = 0; $i < $count; $i++) {
            $sql = preg_replace('/\?/', $this->getPdo()->quote($condition[$i]), $sql, 1);
        }

        // 替换冒号
        $sql = preg_replace_callback('/:(\w+)/', function ($matches) use ($condition) {
            if (isset($condition[$matches[1]])) {
                return $this->getPdo()->quote($condition[$matches[1]]);
            } else if (isset($condition[':' . $matches[1]])) {
                return $this->getPdo()->quote($condition[':' . $matches[1]]);
            }
            return $matches[0];
        }, $sql);

        return $sql;
    }


}