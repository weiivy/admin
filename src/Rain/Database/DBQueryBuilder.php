<?php

namespace Rain\Database;

use Exception;

/**
 * SQL构造器
 * @author  Zou Yiliang
 */
class DBQueryBuilder
{

    const PARAM_PREFIX = ':pre_';

    /**
     * 返回INSERT语句
     * @param $tableName
     * @param array $data 列名只允许字母数字或下划线
     * @return string
     * @throws Exception
     */
    public static function insert($tableName, array &$data)
    {
        $names = [];
        $placeholders = [];
        foreach ($data as $name => $value) {
            if (preg_match('/^\w+$/', $name)) {
                $names[] = '[[' . $name . ']]';
                $phName = ':' . $name;
                $placeholders[] = $phName;
            }
        }

        return 'INSERT INTO '
        . self::addTablePrefix($tableName)
        . ' (' . implode(', ', $names) . ') VALUES ('
        . implode(', ', $placeholders) . ')';
    }

    /**
     * 返回UPDATE语句
     * @param $tableName
     * @param $data 列名只允许字母数字或下划线
     * @param string $condition
     * @param array $params
     * @return string
     * @throws Exception
     */
    public static function update($tableName, &$data, $condition = '', array &$params = array())
    {
        $updatePlaceholders = [];
        foreach ($data as $name => $value) {
            if (preg_match('/^\w+$/', $name)) {
                $updatePlaceholders[] = "[[$name]]" . ' = ' . self::PARAM_PREFIX . $name;
                $params[self::PARAM_PREFIX . $name] = $value;
            }
        }
        if (count($updatePlaceholders) === 0) {
            throw new Exception('update data error');
        }

        //将问号参数，转为冒号
        $count = substr_count($condition, '?');
        for ($i = 0; $i < $count; $i++) {
            $condition = preg_replace('/\?/', self::PARAM_PREFIX . $i, $condition, 1);
            $params[self::PARAM_PREFIX . $i] = $params[$i];
            unset($params[$i]);
        }

        return 'UPDATE '
        . self::addTablePrefix($tableName)
        . ' SET ' . implode(', ', $updatePlaceholders)
        . (self::isEmpty($condition) ? '' : ' WHERE ' . $condition);
    }

    /**
     * 返回DELETE语句
     * @param $tableName
     * @param string $condition
     * @return string
     * @throws Exception
     */
    public static function delete($tableName, $condition = '')
    {
        return 'DELETE FROM '
        . self::addTablePrefix($tableName)
        . (self::isEmpty($condition) ? '' : ' WHERE ' . $condition);
    }

    /**
     * 添加表前缀占位符
     * @param $name
     * @return string
     * @throws Exception
     */
    public static function addTablePrefix($name)
    {
        if (strpos($name, '{{') === false) {
            $name = '{{%' . $name . '}}';
        }
        if (preg_match('/^\\{\\{%?[\\.\w]+%?\\}\\}$/', $name)) {
            return $name;
        }
        throw new Exception('table name error');
    }

    /**
     * 检查是否为空:  null、''、空数组、空白字符("\t"、"\n"、"\r"等)
     * @param mixed $value
     * @return boolean
     */
    public static function isEmpty($value)
    {
        return $value === '' || $value === array() || $value === null || is_string($value) && trim($value) === '';
    }
}