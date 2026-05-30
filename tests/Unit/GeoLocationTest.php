<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Data\GeoLocation;

final class GeoLocationTest extends TestCase
{
    public function test_is_eu_returns_true_when_flag_set(): void
    {
        $geo = new GeoLocation('DE', 'Germany', 'EU', true);

        $this->assertTrue($geo->isEu());
    }

    public function test_is_in_continent_matches_case_insensitively(): void
    {
        $geo = new GeoLocation('US', 'United States', 'NA', false);

        $this->assertTrue($geo->isInContinent('na'));
        $this->assertFalse($geo->isInContinent('EU'));
    }

    public function test_country_name_or_code_prefers_name(): void
    {
        $geo = new GeoLocation('UA', 'Ukraine');

        $this->assertSame('Ukraine', $geo->countryNameOrCode());
    }

    public function test_from_country_code_normalizes_case(): void
    {
        $geo = GeoLocation::fromCountryCode('ua');

        $this->assertSame('UA', $geo->countryCode);
    }
}
