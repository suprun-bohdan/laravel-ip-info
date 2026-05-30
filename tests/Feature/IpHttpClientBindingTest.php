<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Contracts\IpHttpClient;
use SuprunBohdan\IpInfo\Contracts\SchemaInspector;
use SuprunBohdan\IpInfo\Laravel\Http\LaravelIpHttpClient;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpHttpClientBindingTest extends TestCase
{
    public function test_it_resolves_ip_http_client_from_container(): void
    {
        $client = $this->app->make(IpHttpClient::class);

        $this->assertInstanceOf(LaravelIpHttpClient::class, $client);
    }

    public function test_it_resolves_schema_inspector_from_container(): void
    {
        $this->assertInstanceOf(SchemaInspector::class, $this->app->make(SchemaInspector::class));
    }
}
