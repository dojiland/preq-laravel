<?php

namespace Per3evere\Preq;

use Illuminate\Support\Arr;
use Per3evere\Preq\Contract\StateStorage as StateStorageContract;

class CircuitBreakerFactory
{
    /**
     * @var array
     */
    protected $circuitBreakersByCommand = [];

    /**
     * @var $StateStorageContract
     */
    protected $stateStorage;

    public function __construct(StateStorageContract $stateStorage)
    {
        $this->stateStorage = $stateStorage;
    }

    /**
     * 根据所给的命令和配置获取相应的熔断器.
     *
     * @return void
     */
    public function get($commandKey, $commandConfig, CommandMetrics $metrics)
    {
        if (! isset($this->circuitBreakersByCommand[$commandKey])) {
            $circuitBreakerConfig = Arr::get($commandConfig, 'circuitBreaker');

            if (Arr::get($circuitBreakerConfig, 'enabled')) {
                $this->circuitBreakersByCommand[$commandKey] = new CircuitBreaker($commandKey, $metrics, $commandConfig, $this->stateStorage);
            } else {
                $this->circuitBreakersByCommand[$commandKey] = new NoOpCircuitBreaker();
            }
        }

        return $this->circuitBreakersByCommand[$commandKey];
    }
}
