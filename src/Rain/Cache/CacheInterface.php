<?php

namespace Rain\Cache;

/**
 * 缓存驱动
 */
class CacheInterface
{
    public function __construct($config = array())
    {
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }
    }

    public function get($id)
    {
        throw new Exception(get_class($this) . ' does not support ' . __METHOD__ . '().');
    }

    public function mget($ids)
    {
        throw new Exception(get_class($this) . ' does not support ' . __METHOD__ . '().');
    }

    public function set($id, $value, $expire = 0)
    {
        throw new Exception(get_class($this) . ' does not support ' . __METHOD__ . '().');
    }

    //$duration
    public function add($id, $value, $expire = 0)
    {
        throw new Exception(get_class($this) . ' does not support ' . __METHOD__ . '().');
    }

    public function delete($id)
    {
        throw new Exception(get_class($this) . ' does not support ' . __METHOD__ . '().');
    }

    public function flush()
    {
        throw new Exception(get_class($this) . ' does not support ' . __METHOD__ . '().');
    }
}
