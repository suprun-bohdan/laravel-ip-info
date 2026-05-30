<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Providers\HttpIpProvider;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class HttpResilienceTest extends TestCase
{
    public function test_it_soft_fails_on_http_429(): void
    {
        Http::fake([
            '*' => Http::response('', 429),
        ]);

        config([
            'ip-info.http.enabled' => true,
            'ip-info.http.driver' => 'ipinfo',
        ]);

        $provider = $this->app->make(HttpIpProvider::class);
        $result = $provider->lookup(new IpAddress('8.8.8.8'));

        $this->assertFalse($result->resolved);
    }
}
