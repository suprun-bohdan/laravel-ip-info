<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class RequestMacrosTest extends TestCase
{
    public function test_request_macros_resolve_client_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'PL']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);

        $this->assertSame('PL', $request->clientCountry());
        $this->assertTrue($request->isCountry('PL'));
    }

    public function test_request_macros_reuse_middleware_attributes(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        (new ResolveClientIp(app('ip-info')))->handle($request, fn () => response('ok'));

        $this->assertSame('203.0.113.10', $request->clientIp());
        $this->assertSame('UA', $request->ipInfo()->countryCode());
    }
}
