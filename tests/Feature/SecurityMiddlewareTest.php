<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\AllowCountries;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\BlockCountries;
use SuprunBohdan\IpInfo\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class SecurityMiddlewareTest extends TestCase
{
    public function test_block_countries_middleware_aborts_for_blocked_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'RU']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = new BlockCountries(['RU']);

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_allow_countries_middleware_aborts_for_disallowed_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'RU']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = new AllowCountries(['UA', 'PL']);

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_allow_countries_middleware_passes_for_allowed_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = new AllowCountries(['UA']);

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
