<?php

namespace Per3evere\Preq;

use Illuminate\Support\Arr;
use Per3evere\Preq\Contract\StateStorage as StateStorageContract;

class CommandMetricsFactory
{
    /**
     * @var array
     */
    protected $commandMetricsByCommand = [];

    /**
     * @var StateStorageContract
     */
    protected $stateStorage;

    public function __construct(StateStorageContract $stateStorage)
    {
        $this->stateStorage = $stateStorage;
    }

    /**
     * 根据命令关键字获取获取关联的命令度量实例
     *
     * @return void
     */
    public function get($commandKey, $commandConfig)
    {
        if (!isset($this->commandMetricsByCommand[$commandKey])) {
            $metricsConfig = Arr::get($commandConfig, 'metrics');
            $statisticalWindow = Arr::get($metricsConfig, 'rollingStatisticalWindowInMilliseconds');
            $windowBuckets = Arr::get($metricsConfig, 'rollingStatisticalWindowBuckets');
            $snapshotInterval = Arr::get($metricsConfig, 'healthSnapshotIntervalInMilliseconds');

            $counter = new MetricsCounter($commandKey, $this->stateStorage, $statisticalWindow, $windowBuckets);
            $this->commandMetricsByCommand[$commandKey] = new CommandMetrics($counter, $snapshotInterval);
        }

        return $this->commandMetricsByCommand[$commandKey];
    }
}
