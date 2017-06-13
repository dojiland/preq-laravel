<?php

namespace Per3evere\Preq;

use Per3evere\Preq\Contract\Command as CommandContract;

/**
 * Class AbstractCommand
 *
 */
abstract class AbstractCommand implements CommandContract
{
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
}
