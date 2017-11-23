<?php

namespace Per3evere\Preq;

class RequestCache
{
    /**
     * 缓存请求结果，键值为命令名和缓存名.
     */
    protected $cachedResults = [];

    /**
     * 清理给定命令键的缓存
     *
     * @return void
     */
    public function clearAll($commandKey)
    {
        if (isset($this->cachedResults[$commandKey])) {
            unset($this->cachedResults[$commandKey]);
        }
    }

    /**
     * 清理给定命令键和缓存键的缓存
     *
     * @return void
     */
    public function clear($commandKey, $cacheKey)
    {
        if ($this->exists($commandKey, $cacheKey)) {
            unset($this->cachedResults[$commandKey][$cacheKey]);
        }
    }

    /**
     * 根据命令和缓存键来获取缓存.
     *
     * @return void
     */
    public function get($commandKey, $cacheKey)
    {
        if ($this->exists($commandKey, $cacheKey)) {
            return $this->cachedResults[$commandKey][$cacheKey];
        }

        return null;
    }

    /**
     * 注入缓存.
     *
     * @return void
     */
    public function put($commandKey, $cacheKey, $result)
    {
        $this->cachedResults[$commandKey][$cacheKey] = $result;
    }

    /**
     * 如果指定缓存存在，返回 true.
     *
     * @return void
     */
    public function exists($commandKey, $cacheKey)
    {
        return array_key_exists($commandKey, $this->cachedResults)
            && array_key_exists($cacheKey, $this->cachedResults[$commandKey]);
    }
}
