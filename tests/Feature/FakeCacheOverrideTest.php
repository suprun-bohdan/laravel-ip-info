<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class FakeCacheOverrideTest extends TestCase
{
    public function test_fake_overrides_positive_cache(): void
    {
        $cache = $this->app->make(IpCache::class);
        $cache->put(new IpAddress('8.8.8.8'), 'US');

        IpInfo::fake(['8.8.8.8' => 'UA']);

        $this->assertSame('UA', IpInfo::for('8.8.8.8')->countryCode());
        IpInfo::assertLookedUp('8.8.8.8');
    }

    public function test_fake_overrides_negative_cache(): void
    {
        $cache = $this->app->make(IpCache::class);
        $cache->putNegative(new IpAddress('8.8.8.8'));

        IpInfo::fake(['8.8.8.8' => 'UA']);

        $this->assertSame('UA', IpInfo::for('8.8.8.8')->countryCode());
        $this->assertSame('fake', IpInfo::for('8.8.8.8')->result()->provider);
    }

    public function test_fake_does_not_write_to_cache(): void
    {
        $cache = $this->app->make(IpCache::class);

        IpInfo::fake(['8.8.8.8' => 'UA']);
        IpInfo::for('8.8.8.8')->countryCode();

        $this->assertNull($cache->get(new IpAddress('8.8.8.8')));
    }
}
