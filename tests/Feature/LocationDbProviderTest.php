<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Mockery;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderStatus;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\LocationDb\MmdbReaderPool;
use SuprunBohdan\IpInfo\Providers\LocationDbProvider;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class LocationDbProviderTest extends TestCase
{
    private const PUBLIC_IPV4 = '8.8.8.8';

    private const PUBLIC_IPV6 = '2001:4860:4860::8888';

    protected function setUp(): void
    {
        parent::setUp();

        config(['ip-info.location_db.enabled' => true]);
    }

    public function test_it_returns_country_from_mmdb_record(): void
    {
        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4)
            ->andReturn(['country_code' => 'US', 'city' => 'Ashburn']);

        $this->instance(MmdbReaderPool::class, $pool);

        $provider = $this->app->make(LocationDbProvider::class);
        $result = $provider->lookup(new IpAddress(self::PUBLIC_IPV4));

        $this->assertTrue($result->isHit());
        $this->assertSame('US', $result->countryCode);
        $this->assertSame('location_db', $result->provider);
        $this->assertSame('Ashburn', $result->geo?->city);
    }

    public function test_it_resolves_public_ipv6(): void
    {
        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV6)
            ->andReturn(['country_code' => 'US']);

        $this->instance(MmdbReaderPool::class, $pool);

        $provider = $this->app->make(LocationDbProvider::class);
        $result = $provider->lookup(new IpAddress(self::PUBLIC_IPV6));

        $this->assertTrue($result->isHit());
        $this->assertSame('US', $result->countryCode);
    }

    public function test_it_misses_when_reader_returns_null(): void
    {
        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4)
            ->andReturn(null);

        $this->instance(MmdbReaderPool::class, $pool);

        $provider = $this->app->make(LocationDbProvider::class);
        $result = $provider->lookup(new IpAddress(self::PUBLIC_IPV4));

        $this->assertSame(ProviderStatus::Miss, $result->status);
    }

    public function test_it_skips_when_disabled(): void
    {
        config(['ip-info.location_db.enabled' => false]);

        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldNotReceive('lookup');

        $this->instance(MmdbReaderPool::class, $pool);

        $provider = $this->app->make(LocationDbProvider::class);
        $result = $provider->lookup(new IpAddress(self::PUBLIC_IPV4));

        $this->assertSame(ProviderStatus::Skipped, $result->status);
    }

    public function test_chain_resolves_city_via_fluent_api(): void
    {
        config([
            'ip-info.providers.chain' => ['location_db', 'null'],
            'ip-info.location_db.fields' => ['country', 'city'],
        ]);

        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4)
            ->andReturn(['country_code' => 'US', 'city' => 'Ashburn']);

        $this->instance(MmdbReaderPool::class, $pool);

        $this->app->forgetInstance('ip-info');

        $this->assertSame('Ashburn', IpInfo::for(self::PUBLIC_IPV4)->city());
    }
}
