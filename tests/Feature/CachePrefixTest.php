<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use SuprunBohdan\IpInfo\Cache\LaravelCacheIpCache;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class CachePrefixTest extends TestCase
{
    public function test_with_cache_prefix_binds_runtime_namespace(): void
    {
        IpInfo::withCachePrefix('tenant-a');

        $this->assertSame('tenant-a', app('ip-info.runtime_cache_prefix'));
    }

    public function test_cache_key_includes_runtime_prefix(): void
    {
        app()->instance('ip-info.runtime_cache_prefix', 'tenant-b');

        $cache = new LaravelCacheIpCache(new Repository(new ArrayStore), 'laravel_ip_info', 86400, 300);

        $this->assertSame(
            'laravel_ip_info:tenant-b:v1:8.8.8.8',
            $cache->key(new IpAddress('8.8.8.8')),
        );
    }
}
