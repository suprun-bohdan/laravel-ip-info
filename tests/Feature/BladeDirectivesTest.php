<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Contracts\IpProviderResolver;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class BladeDirectivesTest extends TestCase
{
    public function test_country_directive_renders_matching_content(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);
        $this->withClientIp('203.0.113.10');

        $compiled = View::make('ip-info::tests.country', [])->render();

        $this->assertSame('visible', trim($compiled));
    }

    public function test_eu_directive_renders_for_eu_geo(): void
    {
        $provider = new class implements IpProvider
        {
            public function lookup(IpAddress $ip): ProviderResult
            {
                return ProviderResult::hit(
                    'DE',
                    'test',
                    new GeoLocation('DE', 'Germany', 'EU', true),
                );
            }
        };

        $this->app->make(IpProviderResolver::class)
            ->replace(new ChainProvider([$provider]));
        $this->withClientIp('8.8.8.8');

        $compiled = View::make('ip-info::tests.eu', [])->render();

        $this->assertSame('eu-visible', trim($compiled));
    }

    public function test_privateip_directive_renders_for_private_ip(): void
    {
        $this->withClientIp('10.0.0.1');

        $compiled = View::make('ip-info::tests.privateip', [])->render();

        $this->assertSame('private-visible', trim($compiled));
    }

    public function test_tor_directive_renders_for_tor_exit_ip(): void
    {
        config(['ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24']]);
        $this->withClientIp('198.96.155.10');

        $compiled = View::make('ip-info::tests.tor', [])->render();

        $this->assertSame('tor-visible', trim($compiled));
    }

    public function test_clientip_directive_echoes_client_ip(): void
    {
        $this->withClientIp('203.0.113.55');

        $compiled = View::make('ip-info::tests.clientip', [])->render();

        $this->assertSame('203.0.113.55', trim($compiled));
    }

    public function test_dev_banner_component_renders_geo_and_structure(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);
        $this->withClientIp('203.0.113.10');

        $html = View::make('ip-info::tests.dev-banner', [])->render();

        $this->assertStringContainsString('ip-info-banner', $html);
        $this->assertStringContainsString('UA', $html);
        $this->assertStringContainsString('Client IP intelligence', $html);
    }

    public function test_country_gate_component_shows_content_for_matching_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);
        $this->withClientIp('203.0.113.10');

        $html = View::make('ip-info::tests.country-gate', [])->render();

        $this->assertStringContainsString('gate-match', $html);
        $this->assertStringContainsString('ip-info-country-gate', $html);
    }

    public function test_country_gate_component_renders_fallback_for_non_matching_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'US']);
        $this->withClientIp('203.0.113.10');

        $html = View::make('ip-info::tests.country-gate-fallback', [])->render();

        $this->assertStringContainsString('gate-fallback', $html);
        $this->assertStringNotContainsString('gate-match', $html);
    }

    public function test_blade_assets_publish_tag_is_registered(): void
    {
        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'ip-info-blade',
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists(public_path('vendor/ip-info/ip-info-blade.css'));
        $this->assertFileExists(resource_path('views/vendor/ip-info/components/dev-banner.blade.php'));
    }

    private function withClientIp(string $ip): void
    {
        $this->app->instance('request', request()->create('/', 'GET', server: ['REMOTE_ADDR' => $ip]));
    }
}
