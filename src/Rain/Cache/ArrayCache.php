<?php
namespace Rain\Cache;

/**
 * Array缓存类
 *
 * 将数据存在一个数组中，仅在当前请求有效
 *
 * @author  Zou Yiliang
 */
class ArrayCache extends CacheInterface
{
    private $_cache;
    /**
     * @var string 键(key)的前缀
     */
    public $keyPrefix = '';

    public function get($id)
    {
        $key = $this->generateUniqueKey($id);
        if (isset($this->_cache[$key]) && ($this->_cache[$key][1] === 0 || $this->_cache[$key][1] > microtime(true))) {
            return $this->_cache[$key][0];
        } else {
            return false;
        }
    }

    public function set($id, $value, $expire = 0)
    {
        $key = $this->generateUniqueKey($id);
        $this->_cache[$key] = [$value, $expire === 0 ? 0 : microtime(true) + $expire];
        return true;
    }

    public function add($id, $value, $expire = 0)
    {
        $key = $this->generateUniqueKey($id);
        if (isset($this->_cache[$key]) && ($this->_cache[$key][1] === 0 || $this->_cache[$key][1] > microtime(true))) {
            return false;
        } else {
            $this->_cache[$key] = [$value, $expire === 0 ? 0 : microtime(true) + $expire];
            return true;
        }
    }

    public function delete($id)
    {
        $key = $this->generateUniqueKey($id);
        unset($this->_cache[$key]);
        return true;
    }

    public function flush()
    {
        $this->_cache = [];
        return true;
    }

    protected function generateUniqueKey($key)
    {
        return $this->hashKey ? md5($this->keyPrefix . $key) : $this->keyPrefix . $key;
    }

}
