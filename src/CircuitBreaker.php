<?php

namespace Per3evere\Preq;

use Per3evere\Preq\Contract\CircuitBreaker as CircuitBreakerContract;
use Per3evere\Preq\Contract\StateStorage as StateStorageContract;
use Illuminate\Support\Arr;

class CircuitBreaker implements CircuitBreakerContract
{
    /**
     * @var CommandMetrics
     */
    private $metrics;

    /**
     * @var StateStorageContract
     */
    private $stateStorage;

    /**
     * @var array
     */
    private $config;

    /**
     * 用于区别各种命令.
     *
     * @var string
     */
    private $commandKey;

    /**
     * 初始化.
     *
     * @return void
     */
    public function __construct(
        $commandKey,
        CommandMetrics $metrics,
        array $config,
        StateStorageContract $stateStorage
    )
    {
        $this->commandKey = $commandKey;
        $this->metrics = $metrics;
        $this->config = $config;
        $this->stateStorage = $stateStorage;
    }

    /**
     * 熔断器是否打开. 打开表示不能运行请求
     *
     * @return boolean
     */
    public function isOpen()
    {
        if ($this->stateStorage->isCircuitOpen($this->commandKey)) {
            return true;
        }

        $healthCounts = $this->metrics->getHealthCounts();

        if ($healthCounts->getTotal() < Arr::get($this->config, 'circuitBreaker.requestVolumeThreshold')) {
            return false;
        }

        $allowedErrorPercentage = Arr::get($this->config, 'circuitBreaker.errorThresholdPercentage');

        if ($healthCounts->getErrorPercentage() < $allowedErrorPercentage) {
            return false;
        } else {
            $this->stateStorage->openCircuit(
                $this->commandKey,
                Arr::get($this->config, 'circuitBreaker.sleepWindowInMilliseconds'),
                Arr::get($this->config, 'circuitBreaker.closeCircuitBreakerInSeconds'),
            );
            return true;
        }
    }

    /**
     * 是否允许单一测试.
     *
     * @return boolean
     */
    public function allowSingleTest()
    {
        return $this->stateStorage->allowSingleTest(
            $this->commandKey,
            Arr::get($this->config, 'circuitBreaker.sleepWindowInMilliseconds')
        );
    }

    /**
     * 请求是否允许.
     *
     * @return boolean
     */
    public function allowRequest()
    {
        if (Arr::get($this->config, 'circuitBreaker.forceOpen')) {
            return false;
        }

        if (Arr::get($this->config, 'circuitBreaker.forceClosed')) {
            return true;
        }

        return !$this->isOpen() || $this->allowSingleTest();
    }

    /**
     * 标记为成功的请求.
     *
     * @return void
     */
    public function markSuccess()
    {
        if ($this->stateStorage->isCircuitOpen($this->commandKey)) {
            $this->stateStorage->closeCircuit($this->commandKey);
            $this->metrics->resetCounter();
        }
    }
}
