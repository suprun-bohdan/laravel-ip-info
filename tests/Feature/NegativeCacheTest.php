<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class NegativeCacheTest extends TestCase
{
    public function test_it_caches_negative_lookup_results(): void
    {
        config(['ip-info.cache.negative_ttl' => 600]);

        IpInfo::fake(['8.8.8.8' => null]);

        $first = IpInfo::for('8.8.8.8')->result();
        $second = IpInfo::for('8.8.8.8')->result();

        $this->assertNull($first->countryCode());
        $this->assertSame('fake', $first->provider);
        $this->assertNull($second->countryCode());
        $this->assertSame('cache:negative', $second->provider);
    }
}
