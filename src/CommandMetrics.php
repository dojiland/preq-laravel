<?php

namespace Per3evere\Preq;

class CommandMetrics
{
    /**
     * @var MetricsCounter
     */
    private $counter;

    /**
     * @var int
     */
    private $healthSnapshotIntervalInMilliseconds = 1000;

    /**
     * @var HealthCountsSnapshot
     */
    private $lastSnapshot;

    public function __construct(MetricsCounter $counter, $snapshotInterval)
    {
        $this->counter = $counter;
        $this->healthSnapshotIntervalInMilliseconds = $snapshotInterval;
    }

    /**
     * 增加成功 counter
     *
     * @return void
     */
    public function markSuccess()
    {
        $this->counter->add(MetricsCounter::SUCCESS);
    }

    /**
     * 增加类型为响应缓存的计数
     *
     * @return void
     */
    public function markResponseFromCache()
    {
        $this->counter->add(MetricsCounter::RESPONSE_FROM_CACHE);
    }

    /**
     * 增加失败的计数.
     *
     * @return void
     */
    public function markFailure()
    {
        $this->counter->add(MetricsCounter::FAILURE);
    }

    /**
     * 增加 fallback 获取成功计数.
     *
     * @return void
     */
    public function markFallbackSuccess()
    {
        $this->counter->add(MetricsCounter::FALLBACK_SUCCESS);
    }

    /**
     * 增加 fallback 获取失败的计数.
     *
     * @return void
     */
    public function markFallbackFailure()
    {
        $this->counter->add(MetricsCounter::FALLBACK_FAILURE);
    }

    /**
     * 增加异常抛出计数.
     *
     * @return void
     */
    public function markExceptionThrown()
    {
        $this->counter->add(MetricsCounter::EXCEPTION_THROWN);
    }

    /**
     * 增加短路计数.
     *
     * @return void
     */
    public function markShortCircuited()
    {
        $this->counter->add(MetricsCounter::SHORT_CIRCUITED);
    }

    /**
     * 重置计数.
     *
     * @return void
     */
    public function resetCounter()
    {
        $this->counter->reset();
    }

    /**
     * 根据给定度量类型获取计数.
     *
     * @return void
     */
    public function getRollingCount($type)
    {
        return $this->counter->get($type);
    }

    /**
     * 返回当前度量快照.
     *
     * @return void
     */
    public function getHealthCounts()
    {
        // current time in milliseconds
        $now = microtime(true) * 1000;
        // we should make a new snapshot in case there isn't one yet or when the snapshot interval time has passed
        if (!$this->lastSnapshot
            || $now - $this->lastSnapshot->getTime() >= $this->healthSnapshotIntervalInMilliseconds) {
            $this->lastSnapshot = new HealthCountsSnapshot(
                $now,
                $this->getRollingCount(MetricsCounter::SUCCESS),
                $this->getRollingCount(MetricsCounter::FAILURE)
            );
        }
        return $this->lastSnapshot;
    }
}
