<?php
declare(strict_types=1);

namespace Per3evere\Preq\Test;

use PHPUnit\Framework\TestCase;
use Per3evere\Preq\NoOpCircuitBreaker;

class NoOpCircuitBreakerTest extends TestCase
{
    private $circuitBreaker;

    /**
     * @before
     */
    public function setupCircuitBreaker()
    {
        $this->circuitBreaker = new NoOpCircuitBreaker();
    }

    public function testAllowSingleTest()
    {
        $this->assertTrue($this->circuitBreaker->allowSingleTest());
    }

    public function testAllowRequest()
    {
        $this->assertTrue($this->circuitBreaker->allowRequest());
    }

    public function testIsOpen()
    {
        $this->assertFalse($this->circuitBreaker->isOpen());
    }

}
