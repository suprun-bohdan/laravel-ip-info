<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class HelpersTest extends TestCase
{
    public function test_ip_info_helper_resolves_country_for_ip(): void
    {
        IpInfo::fake(['8.8.8.8' => 'US']);

        $this->assertSame('US', ip_info('8.8.8.8')->countryCode());
    }

    public function test_client_country_helper_uses_request(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $this->app->instance('request', Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']));

        $this->assertSame('UA', client_country());
    }

    public function test_client_country_helper_uses_default_when_country_missing(): void
    {
        IpInfo::fake(['203.0.113.10' => null]);

        $this->app->instance('request', Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']));

        $this->assertSame('XX', client_country(default: 'XX'));
    }

    public function test_client_ip_helper_reuses_middleware_attribute(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        (new ResolveClientIp(app('ip-info')))->handle($request, fn () => response('ok'));

        $this->app->instance('request', $request);

        $this->assertSame('203.0.113.10', client_ip());
    }
}
