<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ResolveClientIpMiddlewareTest extends TestCase
{
    public function test_middleware_sets_client_ip_attributes(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = app(ResolveClientIp::class);

        $middleware->handle($request, fn ($req) => response('ok'));

        $this->assertSame('203.0.113.10', $request->attributes->get('client_ip'));
        $this->assertSame('UA', $request->attributes->get('ip_info')->countryCode());
    }
}
