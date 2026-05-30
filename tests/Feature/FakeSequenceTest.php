<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class FakeSequenceTest extends TestCase
{
    public function test_fake_sequence_returns_countries_in_order(): void
    {
        IpInfo::fakeSequence(['US', 'UA', null]);

        $this->assertSame('US', IpInfo::for('1.1.1.1')->countryCode());
        $this->assertSame('UA', IpInfo::for('2.2.2.2')->countryCode());
        $this->assertNull(IpInfo::for('3.3.3.3')->countryCode());
    }

    public function test_assert_looked_up_via_facade(): void
    {
        $fake = IpInfo::fake(['8.8.8.8' => 'US']);

        IpInfo::for('8.8.8.8')->countryCode();

        $this->assertContains('8.8.8.8', $fake->lookups());
    }
}
