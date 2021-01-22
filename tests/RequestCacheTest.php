<?php
declare(strict_types=1);

namespace Per3evere\Preq\Test;

use PHPUnit\Framework\TestCase;
use Per3evere\Preq\RequestCache;

class RequestCacheTest extends TestCase
{
    private $cache;
    private $commandKey;
    private $cacheKey;
    private $result;

    /**
     * @before
     */
    public function setupRequestCache()
    {
        $this->cache = new RequestCache();
        $this->commandKey = 'command.key'.rand(1000, 9999);
        $this->cacheKey = 'cache.key'.rand(1000, 9999);
        $this->result = 'resule'.rand(1000, 9999);
    }

    public function testExists()
    {
        $this->assertFalse($this->cache->exists($this->commandKey, $this->cacheKey));

        $this->cache->put($this->commandKey, $this->cacheKey, $this->result);
        $this->assertTrue($this->cache->exists($this->commandKey, $this->cacheKey));
    }

    public function testPut()
    {
        $this->assertFalse($this->cache->exists($this->commandKey, $this->cacheKey));
        $this->cache->put($this->commandKey, $this->cacheKey, $this->result);
        $this->assertTrue($this->cache->exists($this->commandKey, $this->cacheKey));
        $this->assertEquals($this->result, $this->cache->get($this->commandKey, $this->cacheKey));
    }

    public function testGet()
    {
        $this->assertNull($this->cache->get($this->commandKey, $this->cacheKey));

        $this->cache->put($this->commandKey, $this->cacheKey, $this->result);
        $this->assertEquals($this->result, $this->cache->get($this->commandKey, $this->cacheKey));
    }

    public function testClear()
    {
        $this->cache->put($this->commandKey, $this->cacheKey, $this->result);
        $this->cache->clear($this->commandKey, $this->cacheKey);
        $this->assertFalse($this->cache->exists($this->commandKey, $this->cacheKey));
    }

    public function testClearAll()
    {
        $cacheKey1 = $this->cacheKey. '_1';
        $cacheKey2 = $this->cacheKey. '_2';
        $this->cache->put($this->commandKey, $cacheKey1, $this->result);
        $this->cache->put($this->commandKey, $cacheKey2 . rand(0, 9), $this->result);
        $this->cache->clearAll($this->commandKey);
        $this->assertFalse($this->cache->exists($this->commandKey, $cacheKey1));
        $this->assertFalse($this->cache->exists($this->commandKey, $cacheKey2));
    }
}
