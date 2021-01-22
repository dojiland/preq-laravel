<?php

namespace Per3evere\Preq;

class RequestLog
{
    /**
     * Executed commands
     *
     * @var array
     */
    protected $executedCommands = [];
    /**
     * Returns commands executed during the current request
     *
     * @return array
     */
    public function getExecutedCommands()
    {
        return $this->executedCommands;
    }
    /**
     * Adds an executed command
     *
     * @param AbstractCommand $command
     */
    public function addExecutedCommand(AbstractCommand $command)
    {
        $this->executedCommands[] = $command;
    }
    /**
     * Formats the log of executed commands into a string usable for logging purposes.
     *
     * Examples:
     *
     * TestCommand[SUCCESS][1ms]
     * TestCommand[SUCCESS][1ms], TestCommand[SUCCESS, RESPONSE_FROM_CACHE][1ms]x4
     * TestCommand[TIMEOUT][1ms]
     * TestCommand[FAILURE][1ms]
     * TestCommand[THREAD_POOL_REJECTED][1ms]
     * TestCommand[THREAD_POOL_REJECTED, FALLBACK_SUCCESS][1ms]
     * TestCommand[FAILURE, FALLBACK_SUCCESS][1ms], TestCommand[FAILURE, FALLBACK_SUCCESS, RESPONSE_FROM_CACHE][1ms]x4
     * GetData[SUCCESS][1ms], PutData[SUCCESS][1ms], GetValues[SUCCESS][1ms], GetValues[SUCCESS, RESPONSE_FROM_CACHE][1ms], TestCommand[FAILURE, FALLBACK_FAILURE][1ms], TestCommand[FAILURE,
     * FALLBACK_FAILURE, RESPONSE_FROM_CACHE][1ms]
     *
     * If a command has a multiplier such as <code>x4</code>, that means this command was executed 4 times with the same events. The time in milliseconds is the sum of the 4 executions.
     *
     * For example, <code>TestCommand[SUCCESS][15ms]x4</code> represents TestCommand being executed 4 times and the sum of those 4 executions was 15ms. These 4 each executed the run() method since
     * <code>RESPONSE_FROM_CACHE</code> was not present as an event.
     *
     * @return string request log
     */
    public function getExecutedCommandsAsString()
    {
        $output = "";
        $executedCommands = $this->getExecutedCommands();
        $aggregatedCommandsExecuted = array();
        $aggregatedCommandExecutionTime = array();
        /** @var AbstractCommand $executedCommand */
        foreach ($executedCommands as $executedCommand) {
            $outputForExecutedCommand = $this->getOutputForExecutedCommand($executedCommand);
            if (!isset($aggregatedCommandsExecuted[$outputForExecutedCommand])) {
                $aggregatedCommandsExecuted[$outputForExecutedCommand] = 0;
            }
            $aggregatedCommandsExecuted[$outputForExecutedCommand] = $aggregatedCommandsExecuted[$outputForExecutedCommand] + 1;
            $executionTime = $executedCommand->getExecutionTimeInMilliseconds();
            if (empty($executionTime) || $executionTime < 0) {
                $executionTime = 0;
            }
            if (isset($aggregatedCommandExecutionTime[$outputForExecutedCommand]) && $executionTime > 0) {
                $aggregatedCommandExecutionTime[$outputForExecutedCommand] = $aggregatedCommandExecutionTime[$outputForExecutedCommand] + $executionTime;
            } else {
                $aggregatedCommandExecutionTime[$outputForExecutedCommand] = $executionTime;
            }
        }
        foreach ($aggregatedCommandsExecuted as $outputForExecutedCommand => $count) {
            if (!empty($output)) {
                $output .= ", ";
            }
            $output .= "{$outputForExecutedCommand}";
            $output .= "[" . $aggregatedCommandExecutionTime[$outputForExecutedCommand] . "ms]";
            if ($count > 1) {
                $output .= "x{$count}";
            }
        }
        return $output;
    }
    /**
     * @param AbstractCommand $executedCommand
     * @return string
     */
    protected function getOutputForExecutedCommand(AbstractCommand $executedCommand)
    {
        $display = $executedCommand->getCommandKey() . "[";
        $events = $executedCommand->getExecutionEvents();
        if (count($events) > 0) {
            foreach ($events as $event) {
                $display .= "{$event}, ";
            }
            $display = substr($display, 0, -2);
        } else {
            $display .= "Executed";
        }
        $display .= "]";
        return $display;
    }
}
