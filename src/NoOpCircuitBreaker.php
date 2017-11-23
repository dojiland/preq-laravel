<?php

namespace Per3evere\Preq;

use Per3evere\Preq\Contract\CircuitBreaker as CircuitBreakerContract;

class NoOpCircuitBreaker implements CircuitBreakerContract
{
    /**
     * 始终运行单一测试.
     *
     * @return void
     */
    public function allowSingleTest()
    {
        return true;
    }

    /**
     * 请求始终允许.
     *
     * @return void
     */
    public function allowRequest()
    {
        return true;
    }

    /**
     * 熔断器一直关闭
     *
     * @return void
     */
    public function isOpen()
    {
        return false;
    }

    public function markSuccess()
    {
    }
}
