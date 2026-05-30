<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use SuprunBohdan\IpInfo\Contracts\ReverseDnsResolver;
use SuprunBohdan\IpInfo\Intel\VerifiedCrawlerInspector;
use SuprunBohdan\IpInfo\Tests\Support\FakeReverseDnsResolver;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class VerifiedCrawlerInspectorTest extends TestCase
{
    public function test_it_verifies_crawler_with_forward_dns_match(): void
    {
        config([
            'ip-info.verified_crawlers.enabled' => true,
            'ip-info.verified_crawlers.host_suffixes' => ['.googlebot.com'],
        ]);

        $dns = new FakeReverseDnsResolver;
        $dns->register('66.249.66.1', ' crawl-66-249-66-1.googlebot.com');

        $this->app->instance(ReverseDnsResolver::class, $dns);
        $this->app->forgetInstance(VerifiedCrawlerInspector::class);

        $inspector = new VerifiedCrawlerInspector($dns, app(CacheRepository::class));

        $this->assertTrue($inspector->isVerified('66.249.66.1'));
    }

    public function test_it_rejects_mismatched_forward_dns(): void
    {
        config([
            'ip-info.verified_crawlers.enabled' => true,
            'ip-info.verified_crawlers.host_suffixes' => ['.googlebot.com'],
        ]);

        $dns = new FakeReverseDnsResolver;
        $dns->register('66.249.66.1', 'crawl-66-249-66-1.googlebot.com', '8.8.8.8');

        $inspector = new VerifiedCrawlerInspector($dns, app(CacheRepository::class));

        $this->assertFalse($inspector->isVerified('66.249.66.1'));
    }

    public function test_it_rejects_unknown_host_suffix(): void
    {
        config([
            'ip-info.verified_crawlers.enabled' => true,
            'ip-info.verified_crawlers.host_suffixes' => ['.googlebot.com'],
        ]);

        $dns = new FakeReverseDnsResolver;
        $dns->register('1.2.3.4', 'evil-bot.example.com');

        $inspector = new VerifiedCrawlerInspector($dns, app(CacheRepository::class));

        $this->assertFalse($inspector->isVerified('1.2.3.4'));
    }

    public function test_it_uses_cache_for_subsequent_lookups(): void
    {
        config([
            'ip-info.verified_crawlers.enabled' => true,
            'ip-info.verified_crawlers.host_suffixes' => ['.googlebot.com'],
        ]);

        $dns = new FakeReverseDnsResolver;
        $dns->register('66.249.66.1', 'crawl-66-249-66-1.googlebot.com');

        $inspector = new VerifiedCrawlerInspector($dns, app(CacheRepository::class));

        $this->assertTrue($inspector->isVerified('66.249.66.1'));

        $dns->register('66.249.66.1', 'spoofed.example.com', '8.8.8.8');

        $this->assertTrue($inspector->isVerified('66.249.66.1'));
    }
}
