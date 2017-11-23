<?php

namespace Per3evere\Preq;

use ReflectionClass;

/**
 * 所有的命令由此创建
 * 启动命令时注入相关依赖
 */
class CommandFactory
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var CircuitBreakerFactory
     */
    protected $circuitBreakerFactory;

    /**
     * @var CommandMetricsFactory
     */
    protected $commandMetricsFactory;

    /**
     * @var RequestCache
     */
    protected $requestCache;

    /**
     * @var RequestLog
     */
    protected $requestLog;

    /**
     * Constructs a new Command object with an associative array of default settings.
     *
     * @param array $config
     *
     * @return void
     */
    public function __construct(
        array $config = [],
        CircuitBreakerFactory $circuitBreakerFactory,
        CommandMetricsFactory $commandMetricsFactory,
        RequestCache $requestCache = null,
        RequestLog $requestLog = null
    )
    {
        $this->config = $config;
        $this->circuitBreakerFactory = $circuitBreakerFactory;
        $this->commandMetricsFactory = $commandMetricsFactory;
        $this->requestCache = $requestCache;
        $this->requestLog = $requestLog;
    }

    /**
     * 获取和初始化命令
     *
     * @return
     */
    public function getCommand($class)
    {
        $parameters = func_get_args();
        array_shift($parameters);

        $reflection = new ReflectionClass($class);

        $command = empty($parameters) ?
            $reflection->newInstance() :
            $reflection->newInstanceArgs($parameters);

        $command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $command->setCommandMetricsFactory($this->commandMetricsFactory);
        $command->initializeConfig($this->config);

        if ($this->requestCache) {
            $command->setRequestCache($this->requestCache);
        }

        if ($this->requestLog) {
            $command->setRequestLog($this->requestLog);
        }

        return $command;
    }
}
