<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Support\UrlAllowlistGuard;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class UrlAllowlistGuardTest extends TestCase
{
    public function test_it_rejects_missing_host(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('host');

        UrlAllowlistGuard::assertAllowlisted('https:///%s', ['example.com']);
    }

    public function test_it_rejects_disallowed_host(): void
    {
        $this->expectException(ProviderException::class);

        UrlAllowlistGuard::assertAllowlisted('https://evil.example/%s', ['ipinfo.io']);
    }

    public function test_it_rejects_insecure_http_by_default(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Insecure HTTP');

        UrlAllowlistGuard::assertAllowlisted('http://ip-api.com/json/%s', ['ip-api.com']);
    }

    public function test_it_allows_insecure_http_when_opted_in(): void
    {
        UrlAllowlistGuard::assertAllowlisted(
            'http://ip-api.com/json/%s',
            ['ip-api.com'],
            allowInsecure: true,
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_allows_https_for_allowlisted_host(): void
    {
        UrlAllowlistGuard::assertAllowlisted('https://ipinfo.io/%s/json', ['ipinfo.io']);

        $this->addToAssertionCount(1);
    }
}
