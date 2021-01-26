<?php
declare(strict_types=1);

namespace Per3evere\Preq\Test;

use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Cache\{Repository, FileStore};
use Illuminate\Filesystem\Filesystem;
use Per3evere\Preq\CommandFactory;
use Per3evere\Preq\AbstractCommand;
use Per3evere\Preq\IlluminateStateStorage;
use Per3evere\Preq\CircuitBreakerFactory;
use Per3evere\Preq\CircuitBreaker;
use Per3evere\Preq\CommandMetricsFactory;
use Per3evere\Preq\RequestCache;
use Per3evere\Preq\RequestLog;
use Per3evere\Preq\Exceptions\RuntimeException;
use Per3evere\Preq\Exceptions\CircuitBreakException;

class CommandTest extends TestCase
{
    private $factory;
    private $config;

    public function deleteDir($dir)
    {
        if (!$handle = @opendir($dir)) {
            return false;
        }
        while (false !== ($file = readdir($handle))) {
            if ($file !== "." && $file !== "..") {       //排除当前目录与父级目录
                $file = $dir . '/' . $file;
                if (is_dir($file)) {
                    $this->deleteDir($file);
                } else {
                    @unlink($file);
                }
            }

        }
        @rmdir($dir);
    }

    protected function setupFactory(array $config = [], $circuitBreakerStubFlag = false)
    {
        if (empty($config)) {
            $config = require __DIR__.'/../config/preq.php';
        }
        $this->config = $config;

        $dir = __DIR__.'/cache'.rand(100, 999);
        if (is_dir($dir)) {
            $this->deleteDir($dir);
        }
        $stateStorage = new IlluminateStateStorage(new Repository(new FileStore(new Filesystem(), $dir)));
        register_shutdown_function(function () use ($dir) {
            $this->deleteDir($dir);
        });
        if ($circuitBreakerStubFlag) {
            $breakerFactory = new CircuitBreakerFactory($stateStorage);
            $breakerFactory = $this->createStub(CircuitBreakerFactory::class);
            $breaker = $this->createStub(CircuitBreaker::class);
            $breaker->method('allowRequest')->willReturn(false);
            $breakerFactory->method('get')->willReturn($breaker);
        } else {
            $breakerFactory = new CircuitBreakerFactory($stateStorage);
        }
        $this->factory = new CommandFactory(
            $config,
            $breakerFactory,
            new CommandMetricsFactory($stateStorage),
            new RequestCache(),
            new RequestLog()
        );

        return $this->factory;
    }

    public function testRequestCacheEnabled()
    {
        $command = $this->setupFactory()->getCommand(RequestCacheEnabledCommandDummy::class);
        $result = $command->execute();
        $this->assertEquals($result, $command->execute());
        $this->assertEquals($result, $command->execute());
        $this->assertEquals($result, $command->execute());
        $this->assertEquals($result, $command->execute());
        $this->assertEquals($result, $command->execute());
    }

    public function testRequestCacheDisabled()
    {
        $command = $this->setupFactory()->getCommand(RequestCacheDisabledCommandDummy::class);
        $result = $command->execute();
        $this->assertNotEquals($result, $command->execute());
        $this->assertNotEquals($result, $command->execute());
        $this->assertNotEquals($result, $command->execute());
        $this->assertNotEquals($result, $command->execute());
        $this->assertNotEquals($result, $command->execute());
    }

    public function testCircuitBreakerOpenWioutFallback()
    {
        $config = require __DIR__.'/../config/preq.php';
        $config['default']['fallback']['enabled'] = true;
        $factory = $this->setupFactory($config, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Short-circuited and no fallback available');
        $factory->getCommand(CommandDummy::class)->execute();
    }

    public function testCircuitBreakerOpenWithFallback()
    {
        $config = require __DIR__.'/../config/preq.php';
        $config['default']['fallback']['enabled'] = true;
        $factory = $this->setupFactory($config, true);

        $result = $factory->getCommand(CommandDummyWithFallback::class)->execute();
        $this->assertTrue($result);
    }

    public function testCircuitBreakerOpenWithFallbackAndEx()
    {
        $config = require __DIR__.'/../config/preq.php';
        $config['default']['fallback']['enabled'] = true;
        $factory = $this->setupFactory($config, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Short-circuited and failed retrieving fallback');
        $factory->getCommand(CommandDummyWithFallbackAndEx::class)->execute();
    }

    public function testCircuitBreakerOpenDisableFallback()
    {
        $config = require __DIR__.'/../config/preq.php';
        $config['default']['fallback']['enabled'] = false;
        $factory = $this->setupFactory($config, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Short-circuited and fallback disabled');
        $factory->getCommand(CommandDummy::class)->execute();
    }

    public function testExecuteWithCircuitBreaker()
    {
        $config = require __DIR__.'/../config/preq.php';
        // 设置熔断器的默认合理配置
        $config['default']['circuitBreaker']['enabled'] = true;
        $config['default']['circuitBreaker']['forceOpen'] = false;
        $config['default']['circuitBreaker']['forceClosed'] = false;

        // 请求结果次数数据统计的时间间隔
        $config['default']['metrics']['healthSnapshotIntervalInMilliseconds'] = 0;
        // 尝试恢复时间,单位毫秒.表示熔断打开后
        // 每过1秒会通过一条请求，若请求成功关闭熔断，否则继续等待下个周期
        $sleepingWindowInMilliseconds = $config['default']['circuitBreaker']['sleepWindowInMilliseconds'] = 1000;

        // 请求成功
        $requestExecuteSuccessClosure = function () {
            return $this->factory->getCommand(CommandDummyCircuitBreaker::class, true)->execute();
        };
        // 请求失败
        $requestExecuteFailClosure = function () {
            try {
                // 请求执行失败，会抛出异常，捕获指定错误文本
                return $this->factory->getCommand(CommandDummyCircuitBreaker::class, false)->execute();

                // 正常情况下不会执行到这步
                throw new \Exception('another unknown exception');
            } catch (RuntimeException $e) {
                if ($e->getMessage() != 'fail and no fallback available') {
                    // 正常情况下不会执行到这步
                    throw new \Exception('another unknown exception ==> '.$e->getMessage());
                }
            }
        };
        // 模拟执行成功触发熔断，通过捕获熔断异常判断是否触发
        $circuitBreakerInterceptTrueClosure = function () {
            try {
                $this->factory->getCommand(CommandDummyCircuitBreaker::class, true)->execute();

                // 正常情况下，熔断已开启，不会执行到这句
                throw new \Exception('circuit-breaker useless true');
            } catch (RuntimeException $e) {
                // 仅捕获熔断触发异常
                if ($e->getMessage() != 'Short-circuited and no fallback available') {
                    throw new \Exception('circuit-breaker useless true!');
                }
            }
        };
        // 模拟执行失败触发熔断，通过捕获熔断异常判断是否触发
        $circuitBreakerInterceptFalseClosure = function () {
            try {
                $this->factory->getCommand(CommandDummyCircuitBreaker::class, false)->execute();

                // 正常情况下，熔断已开启，不会执行到这句，执行到为异常现象
                throw new \Exception('circuit-breaker useless false');
            } catch (RuntimeException $e) {
                // 仅捕获熔断触发异常
                if ($e->getMessage() != 'Short-circuited and no fallback available') {
                    throw new \Exception('circuit-breaker useless false!');
                }
            }
        };

        // 测试熔断器开关配置项改动
        // 禁止熔断器
        $bakConfig = $config;
        $config['default']['circuitBreaker']['enabled'] = false;
        $factory = $this->setupFactory($config);
        for ($i = 0; $i < $config['default']['circuitBreaker']['requestVolumeThreshold'] * 10; $i++) {
            $requestExecuteFailClosure();
        }

        // 熔断器强制打开
        $config = $bakConfig;
        $config['default']['circuitBreaker']['forceOpen'] = true;
        $factory = $this->setupFactory($config);
        for ($i = 0; $i < $config['default']['circuitBreaker']['requestVolumeThreshold'] * 10; $i++) {
            $circuitBreakerInterceptTrueClosure();
            $circuitBreakerInterceptFalseClosure();
        }

        // 熔断器强制关闭
        $config = $bakConfig;
        $config['default']['circuitBreaker']['forceClosed'] = true;
        $factory = $this->setupFactory($config);
        for ($i = 0; $i < $config['default']['circuitBreaker']['requestVolumeThreshold'] * 10; $i++) {
            $requestExecuteFailClosure();
        }


        $config = $bakConfig;
        // 触发熔断，并等待到尝试恢复时间
        $factory = $this->waitCircuitBreakerUntilSleepMillTime($config, $requestExecuteSuccessClosure,
            $requestExecuteFailClosure, $circuitBreakerInterceptTrueClosure, $circuitBreakerInterceptTrueClosure
        );
        // 第一次执行成功后，后续可以正常执行
        $requestExecuteSuccessClosure();
        $requestExecuteFailClosure();
        $requestExecuteSuccessClosure();
        $requestExecuteFailClosure();
        $requestExecuteSuccessClosure();
        $requestExecuteFailClosure();

        // 触发熔断，并等待到尝试恢复时间
        $factory = $this->waitCircuitBreakerUntilSleepMillTime($config, $requestExecuteSuccessClosure,
            $requestExecuteFailClosure, $circuitBreakerInterceptTrueClosure, $circuitBreakerInterceptTrueClosure
        );
        // 第一次执行失败后，后续需要等待新一轮时间周期，才能尝试恢复
        $requestExecuteFailClosure();
        $circuitBreakerInterceptFalseClosure();
        $circuitBreakerInterceptTrueClosure();
        // 必须使用ceil，因为实际的缓存时间单位是秒，用毫秒可能会存在向上进位的情况
        sleep(intval(ceil($sleepingWindowInMilliseconds / 1000)));
        $requestExecuteSuccessClosure();
        $requestExecuteFailClosure();
        $requestExecuteSuccessClosure();
        $requestExecuteFailClosure();
        $requestExecuteSuccessClosure();
        $requestExecuteFailClosure();

        // 避免警告提示
        $this->assertTrue(true);
    }

    /*
     * 触发熔断，在熔断尝试恢复时间到达后返回
     */
    private function waitCircuitBreakerUntilSleepMillTime(array $config, $requestExecuteSuccessClosure,
        $requestExecuteFailClosure, $circuitBreakerInterceptTrueClosure, $circuitBreakerInterceptFalseClosure
    ) {

        $factory = $this->setupFactory($config);

        $sleepingWindowInMilliseconds = $config['default']['circuitBreaker']['sleepWindowInMilliseconds'];
        // 请求总次数未超过上限，不会去判断熔断开启条件
        // 默认10
        $requestVolumeThreshold = $config['default']['circuitBreaker']['requestVolumeThreshold'];
        // 请求总次数超过上限后，判断错误总次数的百分比占比，达到阈值开启熔断
        // 默认50
        $errorThresholdPercentage = $config['default']['circuitBreaker']['errorThresholdPercentage'];

        // 动态计算开启熔断条件的请求失败次数
        $failTimes = ceil($requestVolumeThreshold * $errorThresholdPercentage / 100);

        // 模拟请求成功标识
        $successd = true;
        // 模拟请求失败标识
        $failed = false;

        // 模拟执行成功
        for ($i = 0; $i < $requestVolumeThreshold - $failTimes; $i++) {
            $requestExecuteSuccessClosure();
        }
        // 模拟执行失败
        for ($i = 0; $i < $failTimes; $i++) {
            $requestExecuteFailClosure();
        }

        // 模拟执行成功，被熔断拦截
        for ($i = 0; $i < 10; $i++) {
            $circuitBreakerInterceptTrueClosure();
        }
        // 模拟执行失败，被熔断拦截
        for ($i = 0; $i < 10; $i++) {
            $circuitBreakerInterceptFalseClosure();
        }

        // 模拟熔断恢复时间已到
        // 必须使用ceil，因为实际的缓存时间单位是秒，用毫秒可能会存在向上进位的情况
        sleep(intval(ceil($sleepingWindowInMilliseconds / 1000)));
        $endMillTime = microtime(true) * 1000;

        return $factory;
    }
}

class CommandDummyCircuitBreaker extends AbstractCommand
{
    private $resultFlag;
    public function __construct($resultFlag = true)
    {
        $this->resultFlag = $resultFlag;
    }

    public function run()
    {
        if ($this->resultFlag) {
            return true;
        } else {
            throw new \Exception('fail');
        }
    }
}

class CommandDummy extends AbstractCommand
{
    public function run() {}
}

class CommandDummyWithFallback extends AbstractCommand
{
    public function getFallback(\Throwable $e)
    {
        return true;
    }

    public function run() {}
}

class CommandDummyWithFallbackAndEx extends AbstractCommand
{
    public function getFallback(\Throwable $e)
    {
        throw new \Exception('fallback throw');
    }

    public function run() {}
}

class RequestCacheEnabledCommandDummy extends AbstractCommand
{
    public function getCacheKey()
    {
        return 'dummy';
    }
    public function run()
    {
        return microtime(true).rand(100000, 999999);
    }
}

class RequestCacheDisabledCommandDummy extends AbstractCommand
{
    public function run()
    {
        return microtime(true).rand(100000, 999999);
    }
}
