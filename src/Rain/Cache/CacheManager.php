<?php

namespace Rain\Cache;

class CacheManager
{
    protected static $cache;

    protected static function getConfig()
    {
        return array(
            'class' => 'Rain\Cache\FileCache',
            'cachePath' => './cache',
            'keyPrefix' => '',
        );
    }

    /**
     * @return \rain\cache\CacheInterface
     */
    protected static function getCache()
    {
        if (self::$cache === null) {
            $config = static::getConfig();
            $class = $config['class'];
            unset($config['class']);
            self::$cache = new $class($config);
        }
        return self::$cache;
    }

    /**
     *
     * @param  string $key
     * @param  mixed $value
     * @param  int $seconds 过期时间(秒)
     * @return void
     */
    public static function put($key, $value, $seconds=0)
    {
        return self::getCache()->set($key, $value, $seconds);
    }

    public static function add($key, $value, $seconds)
    {
        return self::getCache()->add($key, $value, $seconds);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @return mixed
     */
    public static function get($key)
    {
        return self::getCache()->get($key);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public static function forever($key, $value)
    {
        return self::getCache()->set($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string $key
     * @return void
     */
    public static function forget($key)
    {
        return self::getCache()->delete($key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public static function flush()
    {
        return self::getCache()->flush();
    }


    /**
     * Increment the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return int|bool
     */
//    public static function increment($key, $value = 1)
//    {
//        // TODO: Implement increment() method.
//    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return int|bool
     */
//    public static function decrement($key, $value = 1)
//    {
//        // TODO: Implement decrement() method.
//    }


}