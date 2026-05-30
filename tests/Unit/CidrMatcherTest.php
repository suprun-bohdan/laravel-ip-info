<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Support\CidrMatcher;

final class CidrMatcherTest extends TestCase
{
    public function test_ipv4_cidr_match(): void
    {
        $this->assertTrue(CidrMatcher::matches('203.0.113.10', '203.0.113.0/24'));
        $this->assertFalse(CidrMatcher::matches('203.0.114.1', '203.0.113.0/24'));
    }

    public function test_ipv4_exact_match_without_prefix(): void
    {
        $this->assertTrue(CidrMatcher::matches('8.8.8.8', '8.8.8.8'));
    }

    public function test_matches_any(): void
    {
        $this->assertTrue(CidrMatcher::matchesAny('173.245.48.10', ['10.0.0.0/8', '173.245.48.0/20']));
    }

    public function test_ipv6_cidr_match(): void
    {
        $this->assertTrue(CidrMatcher::matches('2001:db8::1', '2001:db8::/32'));
        $this->assertFalse(CidrMatcher::matches('2001:db9::1', '2001:db8::/32'));
    }
}
