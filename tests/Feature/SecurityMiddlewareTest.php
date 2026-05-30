<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        $middleware = new BlockCountries;

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => response('ok'), 'RU');
    }

    public function test_block_countries_middleware_accepts_route_parameters(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = new BlockCountries;

        $response = $middleware->handle($request, fn () => response('ok'), 'RU', 'BY');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_allow_countries_middleware_aborts_for_disallowed_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'RU']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = new AllowCountries;

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => response('ok'), 'UA', 'PL');
    }

    public function test_allow_countries_middleware_passes_for_allowed_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = new AllowCountries;

        $response = $middleware->handle($request, fn () => response('ok'), 'UA');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_geo_block_middleware_alias_is_registered(): void
    {
        Route::middleware('geo.block:RU')->get('/blocked-test', fn () => 'ok');

        IpInfo::fake(['203.0.113.10' => 'RU']);

        $response = $this->call('GET', '/blocked-test', [], [], [], ['REMOTE_ADDR' => '203.0.113.10']);

        $this->assertSame(403, $response->getStatusCode());
    }
}
