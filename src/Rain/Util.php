<?php

namespace Rain;

class Util
{

    /**
     * 获取相对时间
     * 规则：
     * 1分钟内    刚刚
     * 1小时内    XX分钟前
     * 1天内      XX小时前
     * 1-7天内    X天前
     * 7天以外    按格式化参数($format)
     * @param int $time
     * @param string $format
     * @return string
     */
    public static function relativeTime($time, $format = 'Y-m-d')
    {
        //各个时间段的unix时间戳
        $minute = 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $week = $day * 7;
        $currentTime = time();

        //两段Unix时间戳相差的时间戳
        $diff = $currentTime - $time;

        //计算
        if ($diff < $minute) {
            $res = '刚刚';
        } else if ($diff < $hour) {
            $res = floor($diff / $minute) . '分钟前';
        } else if ($diff < $day) {
            $res = floor($diff / $hour) . '小时前';
        } else if ($diff < $week) {
            $res = floor($diff / $day) . '天前';
        } else {
            $res = date($format, $time);
        }

        return $res;
    }

    /**
     * 清除非UTF8的特殊字符
     * @param string $str
     * @return string
     */
    public static function cleanUtf8Str($str)
    {
        if (self::isAscii($str) === false) {
            $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        }
        return $str;
    }

    /**
     * 字符串截取(utf-8) 主要用于标题处理
     *
     * @param $string
     * @param int $length 截取长度(字节数,一个汉字两个字节长度,英文数字一个字节长)
     * @param bool $strip_tags 是否去掉标签
     * @param string $append 附加字符
     * @return string
     */
    public static function cutStringUtf8($string, $length, $strip_tags = true, $append = '...')
    {
        if ($strip_tags) {
            $string = strip_tags($string);
        }
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);
        $str = "";
        for ($i = 0; $i < count($t_string[0]); $i++) {
            $str .= $t_string[0][$i];
            if (strlen(@iconv('utf-8', 'gbk', $str)) >= $length) { // gbk一个汉字长度为2
                if ($i != count($t_string[0]) - 1) $str .= $append;
                break;
            }
        }
        return $str;
    }

    /**
     * 按GBK方式计算字符串长度(一个汉字长度为2，英文字符为1)
     * @param $str
     * @return int
     */
    public static function stringLengthGBK($str)
    {
        return strlen(@iconv('utf-8', 'gbk', $str));
    }

    /**
     * 是否Email
     * @param $str
     * @return bool
     */
    public static function isEmail($str)
    {
        $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
        return is_string($str) && strlen($str) <= 254 && preg_match($pattern, $str);
    }

    /**
     * 是否手机号码
     * @param $str
     * @return bool
     */
    public static function isMobile($str)
    {
        $pattern = '/^1\d{10}$/';
        return is_string($str) && strlen($str) == 11 && preg_match($pattern, $str);
    }

    /**
     * 是否ASCII字符
     * @param $str
     * @return bool
     */
    public static function isAscii($str)
    {
        return (preg_match('/[^\x00-\x7F]/S', $str) == 0);
    }

    /**
     * curl post
     * @param string $url
     * @param string $postData
     * @param array $options
     * @return mixed
     */
    public static function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }

        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * curl get
     *
     * @param string $url
     * @param array $options
     * @return mixed
     */
    public static function curlGet($url = '', $options = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }

        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 将字节转为易读的单位
     * @param $size
     * @param int $digits 保留的小数位数,默认为2位
     * @return string
     */
    public static function convertSize($size, $digits = 2)
    {
        if ($size <= 0) {
            return '0 KB';
        }
        $unit = array('', 'K', 'M', 'G', 'T', 'P');
        $base = 1024;
        $i = floor(log($size, $base));
        $n = count($unit);
        if ($i >= $n) {
            $i = $n - 1;
        }
        return round($size / pow($base, $i), $digits) . ' ' . $unit[$i] . 'B';
    }

    /**
     * 将字节转为易读的整数单位
     * @param $size
     * @return string
     */
    public static function convertSizeInt($size)
    {
        if ($size <= 0) {
            return '0 KB';
        }
        $units = array(3 => 'G', 2 => 'M', 1 => 'K', 0 => 'B');//单位字符,可类推添加更多字符.
        foreach ($units as $i => $unit) {
            if ($i > 0) {
                $n = $size / pow(1024, $i) % pow(1024, $i);
            } else {
                $n = $size % 1024;
            }

            $str = '';
            if ($n != 0) {
                $str .= " $n{$unit} ";
            }
        }
        return $str;
    }

    /**
     * 生成全局唯一标识符，类似 09315E33-480F-8635-E780-7A8E61FB49AA
     * @param null $namespace
     * @return string
     */
    public static function guid($namespace = null)
    {
        static $guid = '';
        $uid = uniqid(mt_rand(), true);

        $data = $namespace;
        $data .= isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();                 // 请求那一刻的时间戳
        $data .= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : rand(0, 999999);  // 访问者操作系统信息
        $data .= isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : rand(0, 999999);          // 服务器IP
        $data .= isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : rand(0, 999999);          // 服务器端口号
        $data .= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : rand(0, 999999);          // 远程IP
        $data .= isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : rand(0, 999999);          // 远程端口

        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

        return $guid;
    }

    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        if ($recursive && !is_dir($parentDir)) {
            static::createDirectory($parentDir, $mode, true);
        }
        $result = mkdir($path, $mode);
        chmod($path, $mode);

        return $result;
    }

    public static function copyDirectory($src, $dst, $options = array())
    {
        if (!is_dir($dst)) {
            static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
        }

        $handle = opendir($src);
        if ($handle === false) {
            throw new \Exception('Unable to open directory: ' . $src);
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_file($from)) {
                copy($from, $to);
                if (isset($options['fileMode'])) {
                    @chmod($to, $options['fileMode']);
                }
            } else {
                static::copyDirectory($from, $to, $options);
            }

        }
        closedir($handle);
    }

    /**
     * 递归删除目录
     * @param string $dir 需要删除的目录
     */
    public static function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    self::removeDirectory("$dir/$file");
                }
            }
            rmdir($dir);
        } else if (file_exists($dir)) {
            unlink($dir);
        }
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int $length
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function random($length = 16)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);

            if ($bytes === false) {
                throw new \RuntimeException('Unable to generate random string.');
            }

            return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
        }

        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

}