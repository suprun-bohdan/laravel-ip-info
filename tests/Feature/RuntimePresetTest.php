<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Support\PresetConfigurator;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class RuntimePresetTest extends TestCase
{
    public function test_active_preset_applies_cloudflare_headers_at_boot(): void
    {
        $this->app['config']->set('ip-info.active_preset', 'cloudflare');
        app(PresetConfigurator::class)->apply();

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '173.245.48.1',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.10',
        ]);

        $this->app['config']->set('ip-info.trusted_proxies.proxy_cidrs', ['173.245.48.0/20']);

        $ip = app(RequestIpResolver::class)->resolve($request);

        $this->assertSame('203.0.113.10', $ip->value);
    }
}
