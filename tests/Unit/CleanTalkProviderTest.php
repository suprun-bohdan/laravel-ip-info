<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Providers\CleanTalkProvider;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class CleanTalkProviderTest extends TestCase
{
    public function test_it_returns_country_from_cleantalk_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    '8.8.8.8' => ['country_code' => 'us'],
                ],
            ], 200),
        ]);

        $this->app['config']->set('ip-info.cleantalk.enabled', true);

        $provider = $this->app->make(CleanTalkProvider::class);
        $result = $provider->lookup(new IpAddress('8.8.8.8'));

        $this->assertTrue($result->resolved);
        $this->assertSame('US', $result->countryCode);
        $this->assertSame('cleantalk', $result->provider);
    }

    public function test_it_skips_lookup_when_disabled(): void
    {
        $this->app['config']->set('ip-info.cleantalk.enabled', false);

        $provider = $this->app->make(CleanTalkProvider::class);
        $result = $provider->lookup(new IpAddress('8.8.8.8'));

        $this->assertFalse($result->resolved);
    }

    public function test_it_soft_fails_on_http_500_for_chain_fallback(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $this->app['config']->set('ip-info.cleantalk.enabled', true);

        $provider = $this->app->make(CleanTalkProvider::class);
        $result = $provider->lookup(new IpAddress('8.8.8.8'));

        $this->assertFalse($result->resolved);
    }

    public function test_it_rejects_non_allowlisted_cleantalk_host(): void
    {
        $this->app['config']->set('ip-info.cleantalk.enabled', true);
        $this->app['config']->set('ip-info.cleantalk.url', 'https://evil.example/?ip=%s');

        $provider = $this->app->make(CleanTalkProvider::class);

        $this->expectException(ProviderException::class);
        $provider->lookup(new IpAddress('8.8.8.8'));
    }
}
