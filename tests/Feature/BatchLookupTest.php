<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Models\IpCountry;
use SuprunBohdan\IpInfo\Support\IpRange;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class BatchLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('ip-info.database.enabled', true);
        $app['config']->set('ip-info.providers.chain', ['database', 'null']);
        $app['config']->set('ip-info.lookup.request_memo', false);
    }

    public function test_for_many_resolves_multiple_ips(): void
    {
        foreach (['8.8.8.8', '8.8.4.4'] as $ip) {
            $long = IpRange::ipv4ToLong($ip);
            IpCountry::query()->create([
                'first_ip' => $long,
                'last_ip' => $long,
                'country' => 'US',
            ]);
        }

        $results = IpInfo::forMany(['8.8.8.8', '8.8.4.4']);

        $this->assertSame('US', $results['8.8.8.8']->countryCode());
        $this->assertSame('US', $results['8.8.4.4']->countryCode());
    }

    public function test_for_many_uses_single_database_query_for_batch(): void
    {
        foreach (['8.8.8.8', '8.8.4.4', '1.1.1.1'] as $ip) {
            $long = IpRange::ipv4ToLong($ip);
            IpCountry::query()->create([
                'first_ip' => $long,
                'last_ip' => $long,
                'country' => 'US',
            ]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        IpInfo::forMany(['8.8.8.8', '8.8.4.4', '1.1.1.1']);

        $selectQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'from "ip_country"'))
            ->count();

        $this->assertSame(1, $selectQueries);
    }
}
