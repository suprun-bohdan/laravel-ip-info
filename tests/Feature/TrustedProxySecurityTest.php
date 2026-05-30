<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class TrustedProxySecurityTest extends TestCase
{
    public function test_header_is_ignored_when_remote_is_not_trusted(): void
    {
        config([
            'ip-info.trusted_proxies.headers' => ['CF-Connecting-IP'],
            'ip-info.trusted_proxies.require_trusted_proxy_for_headers' => true,
            'ip-info.trusted_proxies.proxy_cidrs' => ['173.245.48.0/20'],
            'ip-info.trusted_proxies.respect_laravel' => true,
        ]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.50',
        ]);
        $request->headers->set('CF-Connecting-IP', '8.8.8.8');

        $resolver = $this->app->make(RequestIpResolver::class);
        $address = $resolver->resolve($request);

        $this->assertSame('203.0.113.50', $address->value);
    }

    public function test_header_is_accepted_when_remote_is_in_proxy_cidr(): void
    {
        config([
            'ip-info.trusted_proxies.headers' => ['CF-Connecting-IP'],
            'ip-info.trusted_proxies.require_trusted_proxy_for_headers' => true,
            'ip-info.trusted_proxies.proxy_cidrs' => ['173.245.48.0/20'],
            'ip-info.trusted_proxies.respect_laravel' => false,
        ]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '173.245.48.10',
        ]);
        $request->headers->set('CF-Connecting-IP', '8.8.8.8');

        $resolver = $this->app->make(RequestIpResolver::class);
        $address = $resolver->resolve($request);

        $this->assertSame('8.8.8.8', $address->value);
    }

    public function test_header_is_read_when_requirement_is_disabled(): void
    {
        config([
            'ip-info.trusted_proxies.headers' => ['CF-Connecting-IP'],
            'ip-info.trusted_proxies.require_trusted_proxy_for_headers' => false,
            'ip-info.trusted_proxies.respect_laravel' => false,
        ]);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.50',
        ]);
        $request->headers->set('CF-Connecting-IP', '1.1.1.1');

        $resolver = $this->app->make(RequestIpResolver::class);
        $address = $resolver->resolve($request);

        $this->assertSame('1.1.1.1', $address->value);
    }
}
