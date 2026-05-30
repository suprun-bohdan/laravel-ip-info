<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInfoResultTest extends TestCase
{
    public function test_it_serializes_to_array_and_json(): void
    {
        $result = new IpInfoResult(
            '8.8.8.8',
            new GeoLocation('US', 'United States', 'NA', false),
            true,
            false,
            'database',
        );

        $expected = [
            'ip' => '8.8.8.8',
            'country' => 'US',
            'country_name' => 'United States',
            'continent' => 'NA',
            'is_eu' => false,
            'is_public' => true,
            'is_private' => false,
            'provider' => 'database',
        ];

        $this->assertSame($expected, $result->toArray());
        $this->assertSame($expected, $result->jsonSerialize());
        $this->assertSame(json_encode($expected), json_encode($result));
    }

    public function test_it_anonymizes_ipv4_and_respects_privacy_flag(): void
    {
        config(['ip-info.privacy.log_lookups' => false]);

        $result = new IpInfoResult(
            '203.0.113.50',
            GeoLocation::fromCountryCode('UA'),
            true,
            false,
            'fake',
        );

        $this->assertFalse($result->shouldLog());
        $this->assertSame('203.0.113.0', $result->anonymized()->ip);
    }
}
