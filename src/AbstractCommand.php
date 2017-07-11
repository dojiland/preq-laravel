<?php

namespace Per3evere\Preq;

use Per3evere\Preq\Contract\Command as CommandContract;

/**
 * Class AbstractCommand
 *
 */
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
     * 命令配置
     *
     * @var array
     */
    protected $config;

    /**
     * 初始化配置
     *
     * @return void
     */
    public function initializeConfig(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 执行命令
     * 加入处理逻辑
     *
     * @return mixed
     */
    public function execute()
    {
        $this->prepare();
        $result = $this->run();

        return $result;
    }

    /**
     * 执行命令前置操作
     *
     */
    protected function prepare()
    {
        return null;
    }
}
