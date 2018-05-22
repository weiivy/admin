<?php
/**
 * PHP函数扩展
 * @author  Zou Yiliang <it9981@gmail.com>
 * @since   1.0
 */

if (!function_exists('array_column')) { // PHP < 5.5
    /**
     * 返回数组中指定的一列
     * 返回input数组中键值为column_key的列， 如果指定了可选参数index_key，那么input数组中的这一列的值将作为返回数组中对应值的键。
     * $records = array(
     *        array(
     *            'id' => 2135,
     *            'first_name' => 'John',
     *            'last_name' => 'Doe',
     *        ),
     *        array(
     *            'id' => 3245,
     *            'first_name' => 'Sally',
     *            'last_name' => 'Smith',
     *        ),
     * );
     * $first_names = array_column($records, 'first_name');
     * //Array ( [0] => John [1] => Sally )
     *
     * $last_names = array_column($records, 'last_name', 'id');
     * //Array ( [2135] => Doe [3245] => Smith )
     *
     * @param array $input 需要取出数组列的多维数组（或结果集）
     * @param mixed $columnKey 需要返回值的列，它可以是索引数组的列索引，或者是关联数组的列的键。 也可以是NULL，此时将返回整个数组（配合index_key参数来重置数组键的时候，非常管用）
     * @param mixed $indexKey 作为返回数组的索引/键的列，它可以是该列的整数索引，或者字符串键值。
     * @return array 从多维数组中返回单列数组
     */
    function array_column(Array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        foreach ($input as $key => $row) {
            $value = is_null($columnKey) ? $row : $row[$columnKey];
            if (is_null($indexKey)) {
                $result[] = $value;
            } else {
                $result[$row[$indexKey]] = $value;
            }
        }
        return $result;

        /* 网上复制的:
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array)$input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
        */
    }
}

if (!function_exists('lcfirst')) { // PHP < 5.3.0
    /**
     * 使一个字符串的第一个字符小写
     * @param string $str 输入的字符串
     * @return string 返回转换后的字符串。
     */
    function lcfirst($str)
    {
        if (strlen($str) > 0) {
            $str[0] = strtolower($str[0]);
        }
        return $str;
    }
}

if (!function_exists('array_replace')) { // PHP 5 < 5.3.0
    function array_replace()
    {
        $args = func_get_args();
        $num_args = func_num_args();
        $res = array();
        for ($i = 0; $i < $num_args; $i++) {
            if (is_array($args[$i])) {
                foreach ($args[$i] as $key => $val) {
                    $res[$key] = $val;
                }
            } else {
                trigger_error(__FUNCTION__ . '(): Argument #' . ($i + 1) . ' is not an array', E_USER_WARNING);
                return NULL;
            }
        }
        return $res;
    }
}

if (!function_exists('array_replace_recursive')) { // PHP 5 < 5.3.0
    function array_replace_recursive($base, $replacements)
    {
        foreach (array_slice(func_get_args(), 1) as $replacements) {
            $bref_stack = array(&$base);
            $head_stack = array($replacements);

            do {
                end($bref_stack);

                $bref = &$bref_stack[key($bref_stack)];
                $head = array_pop($head_stack);

                unset($bref_stack[key($bref_stack)]);

                foreach (array_keys($head) as $key) {
                    if (isset($key, $bref) && is_array($bref[$key]) && is_array($head[$key])) {
                        $bref_stack[] = &$bref[$key];
                        $head_stack[] = $head[$key];
                    } else {
                        $bref[$key] = $head[$key];
                    }
                }
            } while (count($head_stack));
        }

        return $base;
    }
}

//打印变量
if (!function_exists('dump')) {
    function dump($arg, $return = false, $layer = 1)
    {
        $html = '';

        //字符串
        if (is_string($arg)) {
            $len = strlen($arg);
            $html .= "<small>string</small> <font color='#cc0000'>'{$arg}'</font>(length={$len})";
        } else if (is_float($arg)) {
            $html .= "<small>float</small> <font color='#f57900'>{$arg}</font>";
        } //布尔
        else if (is_bool($arg)) {
            $html .= "<small>boolean</small> <font color='#75507b'>" . ($arg ? 'true' : 'false') . "</font>";
        } //null
        else if (is_null($arg)) {
            $html .= "<font color='#3465a4'>null</font>";
        } //资源
        else if (is_resource($arg)) {
            $type = get_resource_type($arg);
            $html .= "<small>resource</small>(<i>{$type}</i>)";
        } //整型
        else if (is_int($arg)) {
            $html .= "<small>int</small> <font color='#4e9a06'>" . $arg . "</font>";
        } //数组
        else if (is_array($arg)) {
            $count = count($arg);
            $html .= "<b>array</b> (size={$count})";
            if (count($arg) == 0) {
                $html .= "\n" . str_pad(' ', $layer * 4) . "empty";
            }

            foreach ($arg as $key => $value) {
                $html .= "\n" . str_pad(' ', $layer * 4) . "'{$key}' => ";
                $html .= dump($value, true, $layer + 1);
            }
        } //对象
        else if (is_object($arg)) {

            if ($arg instanceof CModel) {

                $class = get_class($arg);
                $html .= "<div>" . str_pad(' ', ($layer - 1) * 4) . "<b>object</b>({$class})";

                foreach ($arg->attributes as $k => $v) {
                    $html .= "\n" . str_pad(' ', $layer * 4) . "<i>public</i> '{$k}'=>" . dump($v, true, $layer + 1);
                }

                ob_start();
                var_dump($arg);
                $output = ob_get_clean();
                $html .= "</div><div style='cursor:pointer;color:red;' onclick='this.nextSibling.style.display=\"block\";this.style.display=\"none\";this.previousSibling.style.display=\"none\"'>" . str_pad(' ', $layer * 4) . "more...</div>";
                $html .= "<div style='display: none;' >$output</div>";
            } else {
                ob_start();
                var_dump($arg);
                $html .= ob_get_clean();
            }
        } //未知
        else {
            ob_start();
            var_dump($arg);
            $html .= ob_get_clean();
        }

        if ($return === true) {
            return $html;
        } else {
            echo '<pre>';
            echo $html;
            echo '</pre>';
        }
    }
}