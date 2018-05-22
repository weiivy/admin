<?php
namespace Rain;

class Log
{
    /**
     * @return string|null
     */
    protected static function getLogFile($type)
    {
        if (isset(Application::$app['log.path'])) {
            $path = rtrim(Application::$app['log.path'], '/\\') . '/';
        } else {
            $path = Application::$app['path'] . '/runtime/logs/';
        }

        $file = $path . lcfirst($type) . '.log';

        if (!file_exists(dirname($file))) {
            if (!@mkdir(dirname($file), 0777, true)) {
                return null;
            }
        }
        return $file;
    }

    public static function debug($msg)
    {
        if (Application::$app['debug']) {
            static::write('Debug', $msg);
        }
    }

    public static function info($msg)
    {
        static::write('Info', $msg);
    }

    public static function warning($msg)
    {
        static::write('Warning', $msg);
    }

    public static function error($msg)
    {
        static::write('Error', $msg);
    }

    protected static function write($type, $msg)
    {
        $inFile = '';
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        if (count($backtrace) > 1) {
            $inFile = isset($backtrace[1]['file']) ? $backtrace[1]['file'] : '';
            $inFile .= ':' . (isset($backtrace[1]['line']) ? $backtrace[1]['line'] : '');
        }

        $log = date('Y-m-d H:i:s') . "\n";
        $log .= $type . "\t" . static::varToString($msg) . "\n";
        $log .= $inFile === '' ?: "File\t" . $inFile . "\n";
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= "REQUEST_URI\t" . $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $log .= ("\n" . "HTTP_REFERER\t" . $_SERVER['HTTP_REFERER']);
        }

        $log .= "\n\n";

        //如果写到文件中失败，则发送到 PHP 的系统日志
        $logFile = static::getLogFile($type);
        if ($logFile === null || !@error_log($log, 3, $logFile)) {
            error_log($log);
        }
    }

    public static function varToString($var)
    {
        if (is_object($var)) {
            return sprintf('Object(%s)', get_class($var));
        }

        if (is_array($var)) {
            $a = array();
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, static::varToString($v));
            }

            return sprintf("Array(%s)", implode(', ', $a));
        }

        if (is_resource($var)) {
            return sprintf('Resource(%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'false';
        }

        if (true === $var) {
            return 'true';
        }

        return (string)$var;
    }
}