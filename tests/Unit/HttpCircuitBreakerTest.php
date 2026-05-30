<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use SuprunBohdan\IpInfo\Http\HttpCircuitBreaker;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class HttpCircuitBreakerTest extends TestCase
{
    public function test_it_opens_after_failure_threshold(): void
    {
        config([
            'ip-info.http.circuit_breaker.enabled' => true,
            'ip-info.http.circuit_breaker.failure_threshold' => 3,
        ]);

        $cache = new Repository(new ArrayStore);
        $breaker = new HttpCircuitBreaker($cache, 'test');

        $breaker->recordFailure('http:test');
        $breaker->recordFailure('http:test');
        $this->assertFalse($breaker->isOpen('http:test'));

        $breaker->recordFailure('http:test');
        $this->assertTrue($breaker->isOpen('http:test'));
    }

    public function test_success_resets_failures(): void
    {
        config([
            'ip-info.http.circuit_breaker.enabled' => true,
            'ip-info.http.circuit_breaker.failure_threshold' => 2,
        ]);

        $cache = new Repository(new ArrayStore);
        $breaker = new HttpCircuitBreaker($cache, 'test');

        $breaker->recordFailure('http:test');
        $breaker->recordSuccess('http:test');
        $breaker->recordFailure('http:test');

        $this->assertFalse($breaker->isOpen('http:test'));
    }
}
