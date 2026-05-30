<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Contracts\IpProviderResolver;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Laravel\Events\IpInfoBuildingChain;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Laravel\Models\IpCountry;
use SuprunBohdan\IpInfo\Support\IpRange;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class DatabaseRangeProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('ip-info.database.enabled', true);
    }

    public function test_it_looks_up_country_from_offline_database(): void
    {
        $ip = '8.8.8.8';
        $long = IpRange::ipv4ToLong($ip);

        IpCountry::query()->create([
            'first_ip' => $long,
            'last_ip' => $long,
            'country' => 'US',
        ]);

        $this->assertSame('US', IpInfo::for($ip)->countryCode());
    }

    public function test_it_returns_null_when_table_is_empty(): void
    {
        $this->assertNull(IpInfo::for('8.8.8.8')->countryCode());
    }

    public function test_it_matches_range_boundaries(): void
    {
        IpCountry::query()->create([
            'first_ip' => IpRange::ipv4ToLong('93.184.216.0'),
            'last_ip' => IpRange::ipv4ToLong('93.184.216.255'),
            'country' => 'ZZ',
        ]);

        $this->assertSame('ZZ', IpInfo::for('93.184.216.1')->countryCode());
        $this->assertSame('ZZ', IpInfo::for('93.184.216.255')->countryCode());
        $this->assertNull(IpInfo::for('93.184.217.0')->countryCode());
    }

    public function test_it_skips_lookup_when_database_disabled(): void
    {
        config(['ip-info.database.enabled' => false]);

        IpCountry::query()->create([
            'first_ip' => IpRange::ipv4ToLong('8.8.8.8'),
            'last_ip' => IpRange::ipv4ToLong('8.8.8.8'),
            'country' => 'US',
        ]);

        $this->assertNull(IpInfo::for('8.8.8.8')->countryCode());
    }
}

final class CustomProviderChainTest extends TestCase
{
    public function test_it_appends_custom_provider_from_config(): void
    {
        config([
            'ip-info.providers.custom' => [
                StubCountryProvider::class,
            ],
        ]);

        $this->app->forgetInstance(IpInfoManager::class);
        $this->app->forgetInstance(IpProvider::class);
        $this->app->forgetInstance(IpProviderResolver::class);
        $this->app->forgetInstance('ip-info');

        $this->assertSame('XX', IpInfo::for('8.8.8.8')->countryCode());
    }

    public function test_it_allows_event_listener_to_modify_chain(): void
    {
        Event::listen(IpInfoBuildingChain::class, function (IpInfoBuildingChain $event): void {
            $event->providers[] = new StubCountryProvider;
        });

        $this->app->forgetInstance(IpInfoManager::class);
        $this->app->forgetInstance(IpProvider::class);
        $this->app->forgetInstance(IpProviderResolver::class);
        $this->app->forgetInstance('ip-info');

        $this->assertSame('XX', IpInfo::for('8.8.8.8')->countryCode());
    }
}

final class StubCountryProvider implements IpProvider
{
    public function lookup(IpAddress $ip): ProviderResult
    {
        return ProviderResult::hit('XX', 'stub');
    }
}
