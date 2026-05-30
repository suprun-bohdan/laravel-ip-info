<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Privacy\IpPrivacyPolicy;
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
            'city' => null,
            'region' => null,
            'region2' => null,
            'postcode' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'threats' => null,
        ];

        $this->assertSame($expected, $result->toArray());
        $this->assertSame($expected, $result->jsonSerialize());
        $this->assertSame(json_encode($expected), json_encode($result));
    }

    public function test_privacy_policy_controls_logging(): void
    {
        config(['ip-info.privacy.log_lookups' => false]);

        $result = new IpInfoResult(
            '203.0.113.50',
            GeoLocation::fromCountryCode('UA'),
            true,
            false,
            'fake',
        );

        $policy = $this->app->make(IpPrivacyPolicy::class);

        $this->assertFalse($policy->shouldLog($result));
        $this->assertSame('203.0.113.0', $result->anonymized()->ip);
    }
}
