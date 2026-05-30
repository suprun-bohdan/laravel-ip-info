<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Cache\LaravelCacheIpCache;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Providers\LocalProvider;
use SuprunBohdan\IpInfo\Providers\NullProvider;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class ChainProviderTest extends TestCase
{
    public function test_it_stops_on_local_private_ip(): void
    {
        $validator = new IpValidator;
        $chain = new ChainProvider([
            new LocalProvider($validator),
            new NullProvider,
        ]);

        $result = $chain->lookup(new IpAddress('10.0.0.1'));

        $this->assertTrue($result->resolved);
        $this->assertSame('local', $result->provider);
        $this->assertNull($result->countryCode);
    }

    public function test_it_falls_through_when_not_resolved(): void
    {
        $chain = new ChainProvider([
            new NullProvider,
        ]);

        $result = $chain->lookup(new IpAddress('8.8.8.8'));

        $this->assertFalse($result->resolved);
    }
}

final class LaravelCacheIpCacheTest extends TestCase
{
    public function test_it_builds_deterministic_cache_key(): void
    {
        $cache = new LaravelCacheIpCache(null, 'laravel_ip_info', 3600);

        $this->assertSame(
            'laravel_ip_info:v1:8.8.8.8',
            $cache->key(new IpAddress('8.8.8.8'))
        );
    }
}
