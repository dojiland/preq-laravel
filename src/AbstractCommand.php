<?php

namespace Per3evere\Preq;

use Per3evere\Preq\Exceptions\RuntimeException;
use Per3evere\Preq\Exceptions\FallbackNotAvailableException;
use Per3evere\Preq\Exceptions\CircuitBreakException;
use Per3evere\Preq\Exceptions\AsyncCircuitBreakException;
use Per3evere\Preq\Exceptions\BadRequestException;
use Per3evere\Preq\Contract\Command as CommandContract;
use Illuminate\Support\Arr;
use Throwable;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\promise_for;
use GuzzleHttp\Exception\ClientException;

abstract class AbstractCommand implements CommandContract
{
    const EVENT_SUCCESS = 'SUCCESS';
    const EVENT_FAILURE = 'FAILURE';
    const EVENT_TIMEOUT = 'TIMEOUT';
    const EVENT_SHORT_CIRCUITED = 'SHORT_CIRCUITED';
    const EVENT_FALLBACK_SUCCESS = 'FALLBACK_SUCCESS';
    const EVENT_FALLBACK_FAILURE = 'FALLBACK_FAILURE';
    const EVENT_EXCEPTION_THROWN = 'EXCEPTION_THROWN';
    const EVENT_RESPONSE_FROM_CACHE = 'RESPONSE_FROM_CACHE';

    /**
     * 用于区别各种命令.
     *
     * @var string
     */
    protected $commandKey;

    /**
     * 命令配置.
     *
     * @var array
     */
    protected $config;

    /**
     * @var CircuitBreakerFactory
     */
    private $circuitBreakerFactory;

    /**
     * @var CommandMetricsFactory
     */
    private $commandMetricsFactory;

    /**
     * @var RequestCache
     */
    private $requestCache;

    /**
     * @var RequestLog
     */
    private $requestLog;

    /**
     * 执行期间的事件记录.
     *
     * @var array
     */
    private $executionEvents = [];

    /**
     * 执行时间，单位毫秒.
     *
     * @var int
     */
    private $executionTime;

    /**
     * 执行期间可能抛出的异常.
     *
     * @var \Exception
     */
    private $executionException;

    /**
     * 时间戳，单位毫秒.
     *
     * @var int
     */
    private $invocationStartTime;

    /**
     * 获取命令值，用于熔断器分组和追踪度量
     *
     * @return string
     */
    public function getCommandKey()
    {
        if ($this->commandKey) {
            return $this->commandKey;
        } else {
            // 如果 commandKey 未定义的情况下，使用当前类名，使用 . 来替换 \\，防止 hystrix-dashboard 前端渲染问题.
            return str_replace('\\', '.', get_class($this));
        }
    }

    /**
     * 设置熔断器工厂实例.
     */
    public function setCircuitBreakerFactory(CircuitBreakerFactory $circuitBreakerFactory)
    {
        $this->circuitBreakerFactory = $circuitBreakerFactory;
    }

    /**
     * 设置命令度量器工厂实例.
     */
    public function setCommandMetricsFactory(CommandMetricsFactory $commandMetricsFactory)
    {
        $this->commandMetricsFactory = $commandMetricsFactory;
    }

    /**
     * 设置请求缓存.
     */
    public function setRequestCache(RequestCache $requestCache)
    {
        $this->requestCache = $requestCache;
    }

    /**
     * 设置请求日志.
     *
     * @return void
     */
    public function setRequestLog(RequestLog $requestLog)
    {
        $this->requestLog = $requestLog;
    }

    /**
     * 初始化配置.
     */
    public function initializeConfig(array $config = [])
    {
        $commandKey = $this->getCommandKey();

        $config = Arr::get($config, 'default');

        if (Arr::exists($config, $commandKey)) {
            $commandConfig = Arr::get($config, $commandKey);
            $config = array_merge($config, $commandConfig);
        }

        $this->config = $config;
    }

    /**
     * 针对命令设置配置，在运行时覆盖之前配置
     *
     * @return void
     */
    public function setConfig(array $config, $merge = true)
    {
        if ($this->config && $merge) {
            $this->config = array_merge($this->config, $config);
        } else {
            $this->config = $config;
        }
    }

    /**
     * 检查请求缓存是否可用.
     *
     * @return void
     */
    private function isRequestCacheEnabled()
    {
        if (! $this->requestCache) {
            return false;
        }

        return Arr::get($this->config, 'requestCache.enabled') && $this->getCacheKey() !== null;
    }



    /**
     * 执行命令，同步
     * 加入处理逻辑.
     *
     * @return mixed
     */
    public function execute()
    {
        $this->prepare();
        $metrics = $this->getMetrics();
        $cacheEnabled = $this->isRequestCacheEnabled();

        $this->recordExecutedCommand();

        if ($cacheEnabled) {
            $cacheHit = $this->requestCache->exists($this->getCommandKey(), $this->getCacheKey());

            if ($cacheHit) {
                $metrics->markResponseFromCache();
                $this->recordExecutionEvent(self::EVENT_RESPONSE_FROM_CACHE);
                return $this->requestCache->get($this->getCommandKey(), $this->getCacheKey());
            }
        }

        $circuitBreaker = $this->getCircuitBreaker();

        if (! $circuitBreaker->allowRequest()) {
            $metrics->markShortCircuited();
            $this->recordExecutionEvent(self::EVENT_SHORT_CIRCUITED);
            return $this->getFallbackOrThrowException(new CircuitBreakException('Short-circuited'));
        }

        $this->invocationStartTime = $this->getTimeInMilliseconds();
        try {
            $result = $this->run();
            $this->recordExecutionTime();
            $metrics->markSuccess();
            $circuitBreaker->markSuccess();
            $this->recordExecutionEvent(self::EVENT_SUCCESS);
        } catch (BadRequestException $e) {
            $this->recordExecutionTime();
            throw $e;
        } catch (Throwable $e) {
            $this->recordExecutionTime();
            $this->executionException = $e;
            if (!($e instanceof ClientException)) {
                $metrics->markFailure();
            }
            $this->recordExecutionEvent(self::EVENT_FAILURE);
            $result = $this->getFallbackOrThrowException($e);
        }

        if ($cacheEnabled) {
            $this->requestCache->put($this->getCommandKey(), $this->getCacheKey(), $result);
        }

        return $result;
    }

    /**
     * 异步执行命令.
     *
     * @return mixed
     */
    public function queue()
    {
        $this->prepare();
        $metrics = $this->getMetrics();
        $cacheEnabled = $this->isRequestCacheEnabled();

        $this->recordExecutedCommand();

        if ($cacheEnabled) {
            $cacheHit = $this->requestCache->exists($this->getCommandKey(), $this->getCacheKey());

            if ($cacheHit) {
                $metrics->markResponseFromCache();
                $this->recordExecutionEvent(self::EVENT_RESPONSE_FROM_CACHE);
                return $this->requestCache->get($this->getCommandKey(), $this->getCommandKey());
            }
        }

        $circuitBreaker = $this->getCircuitBreaker();

        if (! $circuitBreaker->allowRequest()) {
            $metrics->markShortCircuited();
            $this->recordExecutionEvent(self::EVENT_SHORT_CIRCUITED);
            return $this->getFallbackOrThrowException(new AsyncCircuitBreakException('Short-circuited'));
        }

        $this->invocationStartTime = $this->getTimeInMilliseconds();

        $promise = $this->runAsync();

        if ($promise instanceof PromiseInterface) {
            $promise->then(
                function ($value) use ($metrics, $circuitBreaker) {
                    $this->recordExecutionTime();
                    $metrics->markSuccess();
                    $circuitBreaker->markSuccess();
                    $this->recordExecutionEvent(self::EVENT_SUCCESS);
                },
                function ($reason) use ($metrics) {
                    $this->recordExecutionTime();

                    if ($reason instanceof BadRequestException) {
                        throw $reason;
                    }

                    if (!($reason instanceof ClientException)) {
                        $metrics->markFailure();
                    }

                    $this->executionException = $reason;
                    $this->recordExecutionEvent(self::EVENT_FAILURE);
                    return promise_for($this->getFallbackOrThrowException($reason));
                }
            );

            return $promise;
        }
    }


    /**
     * 执行命令前置操作.
     */
    protected function prepare()
    {
        return;
    }

    /**
     * 自定义事件处理器.
     *
     * @return void
     */
    public function processExecutionEvent($eventName)
    {
    }

    /**
     * 记录发生的事件.
     *
     * @return void
     */
    private function recordExecutionEvent($eventName)
    {
        $this->executionEvents[] = $eventName;

        $this->processExecutionEvent($eventName);
    }

    /**
     * 获取当前命令的 Metrics.
     *
     * @return void
     */
    private function getMetrics()
    {
        return $this->commandMetricsFactory->get($this->getCommandKey(), $this->config);
    }

    /**
     * 获取当前命令的熔断器.
     *
     * @return void
     */
    private function getCircuitBreaker()
    {
        return $this->circuitBreakerFactory->get($this->getCommandKey(), $this->config, $this->getMetrics());
    }

    /**
     * 尝试获取 fallback
     *
     * @return void
     */
    private function getFallbackOrThrowException(Throwable $originalException = null)
    {
        $metrics = $this->getMetrics();

        $message = $originalException === null ? 'Short-circuited' : $originalException->getMessage();

        try {
            if (Arr::get($this->config, 'fallback.enabled')) {
                try {
                    $executionResult = $this->getFallback($originalException);
                    $metrics->markFallbackSuccess();
                    $this->recordExecutionEvent(self::EVENT_FALLBACK_SUCCESS);
                    return $executionResult;
                } catch (FallbackNotAvailableException $fallbackException) {
                    throw new RuntimeException(
                        $message . ' and no fallback available',
                        get_class($this),
                        $originalException
                    );
                } catch (Throwable $fallbackException) {
                    $metrics->markFallbackFailure();
                    $this->recordExecutionEvent(self::EVENT_FALLBACK_FAILURE);
                    throw new RuntimeException(
                        $message . ' and failed retrieving fallback',
                        get_class($this),
                        $originalException,
                        $fallbackException
                    );
                }
            } else {
                throw new RuntimeException(
                    $message . ' and fallback disabled',
                    get_class($this),
                    $originalException
                );
            }
        } catch (Throwable $e) {
            $metrics->markExceptionThrown();
            $this->recordExecutionEvent(self::EVENT_EXCEPTION_THROWN);
            throw $e;
        }
    }

    /**
     * 执行失败时获取.
     *
     * @return void
     */
    protected function getFallback(Throwable $e)
    {
        throw new FallbackNotAvailableException('No fallback available');
    }

    protected function getCacheKey()
    {
        return null;
    }

    /**
     * 获取事件集合
     *
     * @return void
     */
    public function getExecutionEvents()
    {
        return $this->executionEvents;
    }

    /**
     * 记录执行时间.
     *
     * @return void
     */
    private function recordExecutionTime()
    {
        $this->executionTime = $this->getTimeInMilliseconds() - $this->invocationStartTime;
    }

    /**
     * 返回执行时间，单位毫秒
     *
     * @return null|int
     */
    public function getExecutionTimeInMilliseconds()
    {
        return $this->executionTime;
    }

    /**
     * 返回命令执行期间抛出的异常.
     *
     * @return Exception|null
     */
    public function getExecutionException()
    {
        return $this->executionException;
    }

    /**
     * 获取当前服务时间，单位毫秒.
     *
     * @return float
     */
    private function getTimeInMilliseconds()
    {
        return floor(microtime(true) * 1000);
    }

    /**
     * 记录执行命令.
     *
     * @return void
     */
    private function recordExecutedCommand()
    {
        if ($this->requestLog && Arr::get($this->config, 'requestLog.enabled')) {
            $this->requestLog->addExecutedCommand($this);
        }
    }
}
