<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Mockery;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\LocationDb\MmdbReaderPool;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInfoManagerCacheGeoTest extends TestCase
{
    private const PUBLIC_IPV4 = '8.8.8.8';

    public function test_second_lookup_returns_cached_city(): void
    {
        config([
            'ip-info.lookup.request_memo' => false,
            'ip-info.providers.chain' => ['location_db', 'null'],
            'ip-info.location_db.enabled' => true,
            'ip-info.location_db.fields' => ['country', 'city', 'timezone'],
            'ip-info.cache.store_geo_fields' => true,
        ]);

        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4)
            ->andReturn([
                'country_code' => 'US',
                'city' => 'Ashburn',
                'timezone' => 'America/New_York',
            ]);

        $this->instance(MmdbReaderPool::class, $pool);
        $this->app->forgetInstance('ip-info');

        $first = IpInfo::for(self::PUBLIC_IPV4)->result();
        $second = IpInfo::for(self::PUBLIC_IPV4)->result();

        $this->assertSame('Ashburn', $first->geo->city);
        $this->assertSame('location_db', $first->provider);
        $this->assertSame('Ashburn', $second->geo->city);
        $this->assertSame('America/New_York', $second->geo->timezone);
        $this->assertSame('cache', $second->provider);
        $this->assertTrue(Cache::store('array')->has('laravel_ip_info:v2:'.self::PUBLIC_IPV4));
    }

    public function test_v1_cache_entry_still_resolves_country(): void
    {
        config([
            'ip-info.lookup.request_memo' => false,
            'ip-info.providers.chain' => ['null'],
            'ip-info.cache.store_geo_fields' => true,
        ]);

        Cache::store('array')->put('laravel_ip_info:v1:'.self::PUBLIC_IPV4, 'DE', 3600);
        $this->app->forgetInstance('ip-info');

        $result = IpInfo::for(self::PUBLIC_IPV4)->result();

        $this->assertSame('DE', $result->countryCode());
        $this->assertNull($result->geo->city);
        $this->assertSame('cache', $result->provider);
    }

    public function test_store_geo_fields_false_uses_v1_country_cache(): void
    {
        config([
            'ip-info.lookup.request_memo' => false,
            'ip-info.providers.chain' => ['location_db', 'null'],
            'ip-info.location_db.enabled' => true,
            'ip-info.cache.store_geo_fields' => false,
        ]);

        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4)
            ->andReturn(['country_code' => 'US', 'city' => 'Ashburn']);

        $this->instance(MmdbReaderPool::class, $pool);
        $this->app->forgetInstance('ip-info');

        IpInfo::for(self::PUBLIC_IPV4)->result();
        $second = IpInfo::for(self::PUBLIC_IPV4)->result();

        $this->assertTrue(Cache::store('array')->has('laravel_ip_info:v1:'.self::PUBLIC_IPV4));
        $this->assertFalse(Cache::store('array')->has('laravel_ip_info:v2:'.self::PUBLIC_IPV4));
        $this->assertNull($second->geo->city);
        $this->assertSame('cache', $second->provider);
    }
}
