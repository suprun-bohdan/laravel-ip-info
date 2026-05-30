<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Intel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use SuprunBohdan\IpInfo\Contracts\ReverseDnsResolver;

final class VerifiedCrawlerInspector
{
    public function __construct(
        private ReverseDnsResolver $dns,
        private CacheRepository $cache,
    ) {}

    public function isVerified(string $ip): bool
    {
        if (! (bool) config('ip-info.verified_crawlers.enabled', false)) {
            return false;
        }

        $cacheKey = 'ip-info:verified-crawler:'.hash('sha256', $ip);
        $ttl = (int) config('ip-info.verified_crawlers.cache_ttl', 86400);

        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return (bool) $cached;
        }

        $verified = $this->verify($ip);

        $this->cache->put($cacheKey, $verified, $ttl);

        return $verified;
    }

    private function verify(string $ip): bool
    {
        $hostname = $this->dns->getHostByAddr($ip);

        if ($hostname === null || ! $this->matchesSuffix($hostname)) {
            return false;
        }

        $forward = $this->dns->getHostByName($hostname);

        return $forward !== null && $forward === $ip;
    }

    private function matchesSuffix(string $hostname): bool
    {
        $hostname = strtolower($hostname);
        $suffixes = config('ip-info.verified_crawlers.host_suffixes', []);

        if (! is_array($suffixes)) {
            return false;
        }

        foreach ($suffixes as $suffix) {
            if (! is_string($suffix) || $suffix === '') {
                continue;
            }

            $normalized = strtolower($suffix);

            if (str_ends_with($hostname, $normalized)) {
                return true;
            }
        }

        return false;
    }
}
