<?php

namespace Per3evere\Preq;

use ReflectionClass;

/**
 * 所有的命令由此创建
 * 启动命令时注入相关依赖
 *
 */
class CommandFactory
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Constructs a new Command object with an associative array of default settings.
     *
     * @param array $config
     *
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 获取和初始化命令
     *
     * @return
     */
    public function getCommand($class)
    {
        $parameters = func_get_arg();
        array_shift($parameters);

        $reflection = new ReflectionClass($class);

        $command = empty($parameters) ?
            $reflection->newInstance() :
            $reflection->newInstanceArgs($parameters);

        $command->initializeConfig($this->config);

        return $command;
    }
}
