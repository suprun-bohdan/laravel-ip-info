<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInfoResultExtendedTest extends TestCase
{
    public function test_to_minimal_array(): void
    {
        $result = new IpInfoResult(
            '8.8.8.8',
            GeoLocation::fromCountryCode('US'),
            true,
            false,
            'fake',
        );

        $this->assertSame([
            'country' => 'US',
            'is_public' => true,
        ], $result->toMinimalArray());
    }

    public function test_for_logging_anonymizes_and_strips_private_country(): void
    {
        $result = new IpInfoResult(
            '10.0.0.55',
            GeoLocation::fromCountryCode('US'),
            false,
            true,
            'local',
        );

        $logged = $result->forLogging();

        $this->assertSame('10.0.0.0', $logged->ip);
        $this->assertNull($logged->countryCode());
    }

    public function test_is_eu_delegates_to_geo(): void
    {
        $result = new IpInfoResult(
            '8.8.8.8',
            new GeoLocation('DE', 'Germany', 'EU', true),
            true,
            false,
            'test',
        );

        $this->assertTrue($result->isEu());
        $this->assertTrue($result->isInContinent('EU'));
        $this->assertSame('Germany', $result->countryNameOrCode());
    }
}
