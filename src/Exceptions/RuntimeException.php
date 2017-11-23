<?php

namespace Per3evere\Preq\Exceptions;

use Throwable;

class RuntimeException extends \RuntimeException
{
    /**
     * 获取 fallback 时抛出的异常，如果开启的情况下.
     *
     * @var Exception
     */
    private $fallbackException;

    /**
     * 执行命令的名称
     *
     * @var string
     */
    private $commandClass;

    public function __construct(
        $message,
        $commandClass,
        Throwable $originalException = null,
        Throwable $fallbackException = null
    )
    {
        parent::__construct($message, 0, $originalException);
        $this->fallbackException = $fallbackException;
        $this->commandClass = $commandClass;
    }

    /**
     * 获取执行命令名称
     *
     * @return void
     */
    public function getCommandClass()
    {
        return $this->commandClass;
    }

    /**
     * 如果存在，获取 fallback 异常.
     *
     * @return \Exception
     */
    public function getFallbackException()
    {
        return $this->fallbackException;
    }
}
