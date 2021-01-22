<?php
declare(strict_types=1);

namespace Per3evere\Preq\Test;

use PHPUnit\Framework\TestCase;
use Per3evere\Preq\Contract\StateStorage as StateStorageContract;
use Per3evere\Preq\MetricsCounter;

class MetricsCounterTest extends TestCase
{
    private $counter;
    private $cacheTestDouble;

    /**
     * @before
     */
    public function setupCounter()
    {
        $commandKey = 'Per3evere.Preq.TestCommand';
        $this->cacheTestDouble = $cache = $this->createStub(StateStorageContract::class);
        $rollingStatisticalWindowInMilliseconds = 10000;
        $rollingStatisticalWindowBuckets = 10;
        $this->counter = new MetricsCounter($commandKey, $cache, $rollingStatisticalWindowInMilliseconds, $rollingStatisticalWindowBuckets);
    }

    public function testAdd()
    {
        $this->cacheTestDouble->expects($this->once())->method('incrementBucket');
        $this->counter->add('type');
    }

    public function testGet()
    {
        $this->cacheTestDouble->method('getBucket')->willReturn(1);
        $this->cacheTestDouble->expects($this->exactly(10))->method('getBucket');
        $this->assertEquals(10, $this->counter->get('type'));
    }
}
