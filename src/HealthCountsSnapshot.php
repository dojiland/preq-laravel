<?php

namespace Per3evere\Preq;

class HealthCountsSnapshot
{
    /**
     * @var int
     */
    private $successful;

    /**
     * var int
     */
    private $failure;

    /**
     * 产生快照时的时间，单位毫秒
     *
     * @var int
     */
    private $time;

    public function __construct($time, $successful, $failure)
    {
        $this->time = $time;
        $this->failure = $failure;
        $this->successful = $successful;
    }

    /**
     * 返回当前快照生成的时间.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * 返回错误的数量.
     *
     * @return void
     */
    public function getFailure()
    {
        return $this->failure;
    }

    /**
     * 获取成功的数量.
     *
     * @return void
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * 获取发起的总请求数.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->successful + $this->failure;
    }

    /**
     * 返回错误比例.
     *
     * @return float
     */
    public function getErrorPercentage()
    {
        $total = $this->getTotal();

        if (! $total) {
            return 0;
        } else {
            return $this->getFailure() / $total * 100;
        }
    }
}
