<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Providers\HttpIpProvider;
use SuprunBohdan\IpInfo\Support\UrlAllowlistGuard;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class HttpIpProviderTest extends TestCase
{
    public function test_ip_api_driver_returns_country(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
                'countryCode' => 'US',
            ], 200),
        ]);

        config([
            'ip-info.http.enabled' => true,
            'ip-info.http.driver' => 'ip-api',
        ]);

        $provider = $this->app->make(HttpIpProvider::class);
        $result = $provider->lookup(new IpAddress('8.8.8.8'));

        $this->assertTrue($result->resolved);
        $this->assertSame('US', $result->countryCode);
    }

    public function test_url_allowlist_guard_rejects_unknown_host(): void
    {
        $this->expectException(ProviderException::class);

        UrlAllowlistGuard::assertAllowlisted('https://evil.example/%s', ['ip-api.com']);
    }
}
