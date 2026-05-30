<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\ProviderStatus;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp;
use SuprunBohdan\IpInfo\Providers\DatabaseRangeProvider;
use SuprunBohdan\IpInfo\Providers\HttpIpProvider;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class Ipv6IntegrationTest extends TestCase
{
    private const PUBLIC_IPV6 = '2001:4860:4860::8888';

    public function test_fake_lookup_works_for_public_ipv6(): void
    {
        IpInfo::fake([self::PUBLIC_IPV6 => 'US']);

        $this->assertSame('US', IpInfo::for(self::PUBLIC_IPV6)->countryCode());
        $this->assertTrue(IpInfo::for(self::PUBLIC_IPV6)->isPublic());
    }

    public function test_request_resolver_accepts_ipv6_via_laravel_ip(): void
    {
        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => self::PUBLIC_IPV6,
        ]);

        $resolver = $this->app->make(RequestIpResolver::class);
        $address = $resolver->resolve($request);

        $this->assertSame(self::PUBLIC_IPV6, $address->value);
    }

    public function test_request_resolver_fails_for_ipv6_when_laravel_ip_disabled_without_headers(): void
    {
        config(['ip-info.trusted_proxies.respect_laravel' => false]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => self::PUBLIC_IPV6,
        ]);

        $resolver = $this->app->make(RequestIpResolver::class);

        $this->expectException(\SuprunBohdan\IpInfo\Exceptions\InvalidIpAddressException::class);

        $resolver->resolve($request);
    }

    public function test_middleware_and_helpers_work_with_ipv6_client(): void
    {
        IpInfo::fake([self::PUBLIC_IPV6 => 'US']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => self::PUBLIC_IPV6]);
        (new ResolveClientIp(app('ip-info')))->handle($request, fn () => response('ok'));

        $this->app->instance('request', $request);

        $this->assertSame(self::PUBLIC_IPV6, client_ip());
        $this->assertSame('US', client_country());
        $this->assertSame('US', ip_info(self::PUBLIC_IPV6)->countryCode());
    }

    public function test_private_ipv6_returns_null_country_without_external_lookup(): void
    {
        $this->assertNull(IpInfo::for('::1')->countryCode());
        $this->assertTrue(IpInfo::for('::1')->isPrivate());
        $this->assertFalse(IpInfo::for('::1')->isPublic());
    }

    public function test_database_provider_skips_ipv6(): void
    {
        config(['ip-info.database.enabled' => true]);

        $provider = $this->app->make(DatabaseRangeProvider::class);
        $result = $provider->lookup(new IpAddress(self::PUBLIC_IPV6));

        $this->assertSame(ProviderStatus::Skipped, $result->status);
        $this->assertSame('database', $result->provider);
    }

    public function test_http_provider_sends_ipv6_to_api(): void
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
            'ip-info.http.allow_insecure' => true,
        ]);

        $provider = $this->app->make(HttpIpProvider::class);
        $result = $provider->lookup(new IpAddress(self::PUBLIC_IPV6));

        $this->assertTrue($result->isHit());
        $this->assertSame('US', $result->countryCode);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), urlencode(self::PUBLIC_IPV6))
                || str_contains($request->url(), self::PUBLIC_IPV6);
        });
    }

    public function test_ipv6_anonymization_zeroes_host_suffix(): void
    {
        $result = new IpInfoResult(
            self::PUBLIC_IPV6,
            \SuprunBohdan\IpInfo\Data\GeoLocation::fromCountryCode('US'),
            true,
            false,
            'fake',
        );

        // Masks IPv6 to /48 network prefix.
        $this->assertSame('2001:4860:4860::', $result->anonymized()->ip);
    }

    public function test_offline_database_chain_does_not_resolve_public_ipv6(): void
    {
        config([
            'ip-info.database.enabled' => true,
            'ip-info.http.enabled' => false,
            'ip-info.maxmind.enabled' => false,
            'ip-info.cleantalk.enabled' => false,
        ]);

        $this->assertNull(IpInfo::for(self::PUBLIC_IPV6)->countryCode());
    }

    public function test_cloudflare_ipv6_proxy_cidr_is_recognized(): void
    {
        config([
            'ip-info.trusted_proxies.headers' => ['CF-Connecting-IP'],
            'ip-info.trusted_proxies.require_trusted_proxy_for_headers' => true,
            'ip-info.trusted_proxies.proxy_cidrs' => ['2400:cb00::/32'],
            'ip-info.trusted_proxies.respect_laravel' => false,
        ]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '2400:cb00:2048:1::6814:8a2',
        ]);
        $request->headers->set('CF-Connecting-IP', self::PUBLIC_IPV6);

        $resolver = $this->app->make(RequestIpResolver::class);
        $address = $resolver->resolve($request);

        $this->assertSame(self::PUBLIC_IPV6, $address->value);
    }
}
