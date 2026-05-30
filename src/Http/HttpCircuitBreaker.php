<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Http;

use Illuminate\Contracts\Cache\Repository;

final class HttpCircuitBreaker
{
    public function __construct(
        private Repository $cache,
        private string $prefix,
    ) {}

    public function isOpen(string $key): bool
    {
        if (! config('ip-info.http.circuit_breaker.enabled', true)) {
            return false;
        }

        $failures = $this->cache->get($this->cacheKey($key));

        return is_int($failures)
            && $failures >= (int) config('ip-info.http.circuit_breaker.failure_threshold', 5);
    }

    public function recordFailure(string $key): void
    {
        if (! config('ip-info.http.circuit_breaker.enabled', true)) {
            return;
        }

        $cacheKey = $this->cacheKey($key);
        $failures = (int) $this->cache->get($cacheKey, 0) + 1;
        $ttl = (int) config('ip-info.http.circuit_breaker.ttl', 60);

        $this->cache->put($cacheKey, $failures, $ttl);
    }

    public function recordSuccess(string $key): void
    {
        $this->cache->forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return $this->prefix.':circuit:'.$key;
    }
}
