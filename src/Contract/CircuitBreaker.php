<?php

namespace Per3evere\Preq\Contract;

interface CircuitBreaker
{
    /**
     * 熔断器是否开放.
     */
    public function isOpen();

    /**
     * 请求是否允许.
     */
    public function allowRequest();

    public function allowSingleTest();

    /**
     * 标记成功请求.
     */
    public function markSuccess();
}
