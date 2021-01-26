<?php
declare(strict_types=1);

namespace Per3evere\Preq\Test;

use PHPUnit\Framework\TestCase;
use Per3evere\Preq\RequestLog;
use Per3evere\Preq\AbstractCommand;

class RequestLogTest extends TestCase
{
    private $log;

    /**
     * @before
     **/
    public function setupLog()
    {
        $this->log = new RequestLog();
    }

    public function testAddExecutedCommand()
    {
        $command = $this->createStub(AbstractCommand::class);
        $log = $this->log;

        $this->assertEquals([], $log->getExecutedCommands());
        $log->addExecutedCommand($command);
        $this->assertContains($command, $log->getExecutedCommands());
    }

    public function testGetExecutedCommands()
    {
        $command = $this->createStub(AbstractCommand::class);
        $log = $this->log;

        $this->assertEquals([], $log->getExecutedCommands());

        $log->addExecutedCommand($command);
        $this->assertContains($command, $log->getExecutedCommands());
    }

    public function testGetExecutedCommandsAsString()
    {
        $log = $this->log;
        $command = $this->createStub(AbstractCommand::class);
        $command->method('getCommandKey')->willReturn('Per3evere.Preq.TestCommand');
        $command->method('getExecutionEvents')->willReturn([]);
        $log->addExecutedCommand($command);
        $this->assertEquals('Per3evere.Preq.TestCommand[Executed][0ms]', $log->getExecutedCommandsAsString());

        $log->addExecutedCommand($command);
        $this->assertEquals('Per3evere.Preq.TestCommand[Executed][0ms]x2', $log->getExecutedCommandsAsString());

        $command = $this->createStub(AbstractCommand::class);
        $command->method('getCommandKey')->willReturn('Per3evere.Preq.TestCommand');
        $command->method('getExecutionEvents')->willReturn([AbstractCommand::EVENT_SUCCESS]);
        $command->method('getExecutionTimeInMilliseconds')->willReturn(2);
        $log->addExecutedCommand($command);
        $this->assertEquals('Per3evere.Preq.TestCommand[Executed][0ms]x2, Per3evere.Preq.TestCommand[SUCCESS][2ms]', $log->getExecutedCommandsAsString());
    }
}
