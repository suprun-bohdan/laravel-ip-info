<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Models\IpCountry;
use SuprunBohdan\IpInfo\Support\IpRange;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class CacheBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('ip-info.database.enabled', true);
    }

    public function test_it_returns_cached_country_on_second_lookup(): void
    {
        $ip = '8.8.4.4';
        $long = IpRange::ipv4ToLong($ip);

        IpCountry::query()->create([
            'first_ip' => $long,
            'last_ip' => $long,
            'country' => 'US',
        ]);

        $first = IpInfo::for($ip)->result();
        $second = IpInfo::for($ip)->result();

        $this->assertSame('US', $first->countryCode());
        $this->assertSame('database', $first->provider);
        $this->assertSame('US', $second->countryCode());
        $this->assertSame('cache', $second->provider);
    }

    public function test_it_does_not_cache_private_ips(): void
    {
        IpInfo::for('10.0.0.1')->result();
        IpInfo::for('10.0.0.1')->result();

        $this->assertFalse(Cache::store('array')->has('laravel_ip_info:v1:10.0.0.1'));
    }

    public function test_it_uses_configured_cache_ttl(): void
    {
        config(['ip-info.cache.ttl' => 3600]);

        $ip = '8.8.4.4';
        $long = IpRange::ipv4ToLong($ip);

        IpCountry::query()->create([
            'first_ip' => $long,
            'last_ip' => $long,
            'country' => 'US',
        ]);

        IpInfo::for($ip)->result();

        $this->assertTrue(Cache::store('array')->has('laravel_ip_info:v2:'.$ip));
    }
}
