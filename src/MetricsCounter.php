<?php

namespace Per3evere\Preq;

use Per3evere\Preq\Contract\StateStorage as StateStorageContract;

/**
 * 统计命令成功和失败请求.
 */
class MetricsCounter
{
    const
        SUCCESS             = 1,
        FAILURE             = 2,
        TIMEOUT             = 3,
        SHORT_CIRCUITED     = 4,
        FALLBACK_SUCCESS    = 5,
        FALLBACK_FAILURE    = 6,
        EXCEPTION_THROWN    = 8,
        RESPONSE_FROM_CACHE = 9;

    /**
     * @var string
     */
    private $commandKey;

    /**
     * @var StateStorageContract
     */
    private $stateStorage;

    /**
     * @var int
     */
    private $rollingStatisticalWindowInMilliseconds;

    /**
     * @var int
     */
    private $rollingStatisticalWindowBuckets;

    /**
     * @var float
     */
    private $bucketInMilliseconds;

    public function __construct(
        $commandKey,
        StateStorageContract $stateStorage,
        $rollingStatisticalWindowInMilliseconds,
        $rollingStatisticalWindowBuckets
    )
    {
        $this->commandKey = $commandKey;
        $this->stateStorage = $stateStorage;
        $this->rollingStatisticalWindowInMilliseconds = $rollingStatisticalWindowInMilliseconds;
        $this->rollingStatisticalWindowBuckets = $rollingStatisticalWindowBuckets;
        $this->bucketInMilliseconds = $this->rollingStatisticalWindowInMilliseconds / $this->rollingStatisticalWindowBuckets;
    }

    public function add($type)
    {
        $this->stateStorage->incrementBucket($this->commandKey, $type, $this->getCurrentBucketIndex());
    }

    public function get($type)
    {
        $sum = 0;
        $now = $this->getTimeInMilliseconds();

        for ($i = 0; $i < $this->rollingStatisticalWindowBuckets; $i++) {
            $bucketIndex = $this->getBucketIndex($i, $now);
            $sum += $this->stateStorage->getBucket($this->commandKey, $type, $bucketIndex);
        }

        return $sum;
    }

    /**
     * 返回服务器当期时间，单位毫秒.
     *
     * @return void
     */
    private function getTimeInMilliseconds()
    {
        return floor(microtime(true) * 1000);
    }

    /**
     * 对于当前 bucket 返回唯一索引.
     *
     * @return int
     */
    private function getCurrentBucketIndex()
    {
        return $this->getBucketIndex(0, $this->getTimeInMilliseconds());
    }

    /**
     * 根据当前时间返回 bucket 的唯一索引.
     *
     * @return void
     */
    public function getBucketIndex($bucketNumber, $time)
    {
        return floor(($time - $bucketNumber * $this->bucketInMilliseconds) / $this->bucketInMilliseconds);
    }

    /**
     * 充值所有的 metrics.
     *
     * @return void
     */
    public function reset()
    {
        foreach ([self::SUCCESS, self::FAILURE, self::TIMEOUT, self::FALLBACK_SUCCESS, self::FALLBACK_FAILURE, self::EXCEPTION_THROWN, self::RESPONSE_FROM_CACHE] as $type) {
            $now = $this->getTimeInMilliseconds();
            for ($i = 0; $i < $this->rollingStatisticalWindowBuckets; $i++) {
                $bucketIndex = $this->getBucketIndex($i, $now);
                $this->stateStorage->resetBucket($this->commandKey, $type, $bucketIndex);
            }
        }
    }
}
