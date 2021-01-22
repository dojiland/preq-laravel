<?php
declare(strict_types=1);

namespace Per3evere\Preq\Test;

use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Per3evere\Preq\CommandFactory;
use Per3evere\Preq\AbstractCommand;
use Per3evere\Preq\IlluminateStateStorage;
use Per3evere\Preq\CircuitBreakerFactory;
use Per3evere\Preq\CommandMetricsFactory;
use Per3evere\Preq\RequestCache;
use Per3evere\Preq\RequestLog;

class CommandFactoryTest extends TestCase
{
    public function testCommandFactory()
    {
        $config = require __DIR__.'/../config/preq.php';

        $stateStorage = new IlluminateStateStorage($this->createStub(CacheContract::class));

        $factory = new CommandFactory(
            $config,
            new CircuitBreakerFactory($stateStorage),
            new CommandMetricsFactory($stateStorage),
            new RequestCache(),
            $requestLog = new RequestLog()
        );
        $command = $factory->getCommand(TestCommand::class);
        $this->assertInstanceOf(TestCommand::class, $command);

        $this->assertEquals($requestLog, $factory->getRequestLog());
    }
}

class TestCommand extends AbstractCommand
{
    public function run() {}
}
