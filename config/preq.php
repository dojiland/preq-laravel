<?php

return [
    'default' => array( // 默认的命令配置项
        'fallback' => array(
            // 回退操作是否开启
            'enabled' => true,
        ),
        'circuitBreaker' => array(
            // 熔断器是否开启，如果关闭则运行所有的请求
            'enabled' => true,
            // 在打开回路（不允许连贯的请求）前有多少个错误请求
            'errorThresholdPercentage' => 50,
            // If true, the circuit breaker will always be open regardless the metrics
            // 如果为 true, 熔断器一直处于打开状态，无视命令度量
            'forceOpen' => false,
            // If true, the circuit breaker will always be closed, allowing all requests, regardless the metrics
            // 如果为 true, 熔断器一直处于关闭状态，运行所有的请求，无视命令度量
            'forceClosed' => false,
            // How many requests we need minimally before we can start making decisions about service stability
            // 在我们关于服务稳定性做决定是，需要最低限度的请求数量
            'requestVolumeThreshold' => 10,
            // For how long to wait before attempting to access a failing service
            // 在尝试请求一个失败的服务时的等待时间，单位毫秒
            'sleepWindowInMilliseconds' => 5000,
        ),
        'metrics' => array(
            // This is for caching metrics so they are not recalculated more often than needed
            'healthSnapshotIntervalInMilliseconds' => 1000,
            // The period of time within which we the stats are collected
            'rollingStatisticalWindowInMilliseconds' => 1000,
            // The more buckets the more precise and actual the stats and slower the calculation.
            'rollingStatisticalWindowBuckets' => 10,
        ),
        'requestCache' => array(
            // Request cache, if enabled and a command has getCacheKey implemented
            // caches results within current http request
            'enabled' => true,
        ),
        'requestLog' => array(
            // Request log collects all commands executed within current http request
            'enabled' => true,
        ),
    ),
    'MyCommand' => array( // Command specific configuration
        'fallback' => array(
            'enabled' => false
        )
    )
];
