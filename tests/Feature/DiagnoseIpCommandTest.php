<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class DiagnoseIpCommandTest extends TestCase
{
    public function test_it_outputs_json_when_requested(): void
    {
        Artisan::call('ip-info:diagnose', ['ip' => '127.0.0.1', '--json' => true]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('suprun-bohdan/laravel-ip-info', $payload['package']);
        $this->assertArrayHasKey('healthy', $payload);
        $this->assertTrue($payload['healthy']);
        $this->assertArrayHasKey('settings', $payload);
        $this->assertSame('127.0.0.1', $payload['lookup']['ip']);
        $this->assertTrue($payload['lookup']['is_private']);
    }

    public function test_it_resolves_ip_lookup_contract_alias(): void
    {
        $this->assertInstanceOf(IpInfoManager::class, $this->app->make(IpLookupContract::class));
    }
}
