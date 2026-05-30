<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Laravel\IpInfoQuery;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_it_registers_package_config(): void
    {
        $this->assertSame('laravel_ip_info', config('ip-info.cache.prefix'));
        $this->assertFalse(config('ip-info.routes.enabled'));
    }

    public function test_it_resolves_manager_from_container(): void
    {
        $manager = $this->app->make(IpInfoManager::class);

        $this->assertInstanceOf(IpInfoManager::class, $manager);
        $this->assertSame($manager, $this->app->make('ip-info'));
    }

    public function test_facade_resolves_manager(): void
    {
        $this->assertInstanceOf(
            IpInfoQuery::class,
            IpInfo::for('127.0.0.1')
        );
    }
}

final class RequestIpResolverTest extends TestCase
{
    public function test_it_does_not_trust_spoofed_headers_by_default(): void
    {
        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '8.8.8.8',
        ]);

        $ip = IpInfo::forRequest($request)->ip();

        $this->assertSame('203.0.113.10', $ip);
    }

    public function test_it_uses_configured_trusted_header(): void
    {
        config(['ip-info.trusted_proxies.headers' => ['X-Forwarded-For']]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '8.8.8.8',
        ]);

        $ip = IpInfo::forRequest($request)->ip();

        $this->assertSame('8.8.8.8', $ip);
    }

    public function test_it_uses_cf_connecting_ip_when_configured(): void
    {
        config(['ip-info.trusted_proxies.headers' => ['CF-Connecting-IP']]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_CF_CONNECTING_IP' => '1.1.1.1',
        ]);

        $ip = IpInfo::forRequest($request)->ip();

        $this->assertSame('1.1.1.1', $ip);
    }

    public function test_it_normalizes_ipv4_mapped_address_from_trusted_header(): void
    {
        config(['ip-info.trusted_proxies.headers' => ['X-Forwarded-For']]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '::ffff:10.0.0.1',
        ]);

        $query = IpInfo::forRequest($request);

        $this->assertSame('10.0.0.1', $query->ip());
        $this->assertTrue($query->isPrivate());
        $this->assertFalse($query->isPublic());
    }

    public function test_private_ip_is_not_public(): void
    {
        $this->assertTrue(IpInfo::for('192.168.0.1')->isPrivate());
        $this->assertFalse(IpInfo::for('192.168.0.1')->isPublic());
        $this->assertNull(IpInfo::for('192.168.0.1')->countryCode());
    }
}
