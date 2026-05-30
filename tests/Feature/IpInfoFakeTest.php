<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Testing\InteractsWithIpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInfoFakeTest extends TestCase
{
    use InteractsWithIpInfo;

    public function test_fake_returns_mapped_country(): void
    {
        $fake = $this->fakeIpInfo(['203.0.113.1' => 'UA']);

        $this->assertSame('UA', IpInfo::for('203.0.113.1')->countryCode());
        $this->assertSame('fake', IpInfo::for('203.0.113.1')->result()->provider);

        $fake->assertLookedUp('203.0.113.1');
    }

    public function test_fake_via_facade(): void
    {
        IpInfo::fake(['8.8.8.8' => 'US']);

        $this->assertSame('US', IpInfo::for('8.8.8.8')->countryCode());
    }
}
