<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Intel\FilterBlockResponseResolver;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class FilterBlockResponseResolverTest extends TestCase
{
    public function test_it_uses_per_reason_response_when_configured(): void
    {
        config([
            'ip-info.filtering.block_response_status' => 403,
            'ip-info.filtering.block_response_message' => 'Default message',
            'ip-info.filtering.responses' => [
                'tor' => ['status' => 451, 'message' => 'Tor blocked'],
            ],
        ]);

        $resolver = app(FilterBlockResponseResolver::class);
        $response = $resolver->resolve('tor');

        $this->assertSame(451, $response['status']);
        $this->assertSame('Tor blocked', $response['message']);
    }

    public function test_it_falls_back_to_default_response_entry(): void
    {
        config([
            'ip-info.filtering.block_response_status' => 403,
            'ip-info.filtering.block_response_message' => 'Legacy default',
            'ip-info.filtering.responses' => [
                'default' => ['status' => 429, 'message' => 'Too many requests'],
            ],
        ]);

        $resolver = app(FilterBlockResponseResolver::class);
        $response = $resolver->resolve('proxy');

        $this->assertSame(429, $response['status']);
        $this->assertSame('Too many requests', $response['message']);
    }

    public function test_it_falls_back_to_legacy_status_and_message(): void
    {
        config([
            'ip-info.filtering.block_response_status' => 418,
            'ip-info.filtering.block_response_message' => "I'm a teapot",
            'ip-info.filtering.responses' => [],
        ]);

        $resolver = app(FilterBlockResponseResolver::class);
        $response = $resolver->resolve('hosting');

        $this->assertSame(418, $response['status']);
        $this->assertSame("I'm a teapot", $response['message']);
    }
}
