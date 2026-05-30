<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Support\IpRange;

final class IpRangeTest extends TestCase
{
    public function test_it_detects_ipv4_inside_cidr(): void
    {
        $this->assertTrue(IpRange::ipv4InCidr('10.0.0.5', '10.0.0.0/8'));
        $this->assertFalse(IpRange::ipv4InCidr('8.8.8.8', '10.0.0.0/8'));
    }

    public function test_it_converts_ipv4_to_long(): void
    {
        $this->assertSame(ip2long('1.1.1.1'), IpRange::ipv4ToLong('1.1.1.1'));
    }
}
