<?php

namespace Per3evere\Preq\Contract;

interface StateStorage
{
    public function incrementBucket($commandKey, $type, $index);

    public function getBucket($commandKey, $type, $index);

    public function openCircuit($commandKey, $sleepingWindowInMilliseconds, $closeCircuitBreakerInMinutes);

    public function closeCircuit($commandKey);

    public function allowSingleTest($commandKey, $sleepingWindowInMilliseconds);

    public function isCircuitOpen($commandKey);

    public function resetBucket($commandKey, $type, $index);
}
