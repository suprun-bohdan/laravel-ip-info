<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Laravel\Support\PresetConfigurator;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class PresetConfiguratorTest extends TestCase
{
    public function test_applies_cloudflare_preset_to_runtime_config(): void
    {
        $configurator = app(PresetConfigurator::class);

        $this->app['config']->set('ip-info.active_preset', 'cloudflare');

        $this->assertTrue($configurator->apply());
        $this->assertSame(['CF-Connecting-IP'], config('ip-info.trusted_proxies.headers'));
        $this->assertFalse((bool) config('ip-info.trusted_proxies.respect_laravel'));
    }

    public function test_quick_start_preset_enables_http_provider(): void
    {
        $configurator = app(PresetConfigurator::class);

        $presets = (array) config('ip-info.presets', []);

        $this->assertArrayHasKey('quick_start', $presets);
        $configurator->mergePreset($presets['quick_start']);

        $this->assertTrue((bool) config('ip-info.http.enabled'));
        $this->assertSame(['local', 'http', 'null'], config('ip-info.providers.chain'));
    }
}
