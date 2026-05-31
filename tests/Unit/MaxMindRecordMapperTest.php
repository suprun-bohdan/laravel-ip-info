<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\ProviderStatus;
use SuprunBohdan\IpInfo\MaxMind\MaxMindRecordMapper;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class MaxMindRecordMapperTest extends TestCase
{
    private MaxMindRecordMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new MaxMindRecordMapper;
    }

    public function test_it_maps_country_edition_record(): void
    {
        $result = $this->mapper->map([
            'country' => [
                'iso_code' => 'US',
                'names' => ['en' => 'United States'],
                'is_in_european_union' => false,
            ],
            'continent' => ['code' => 'NA'],
        ], 'country');

        $this->assertTrue($result->isHit());
        $this->assertSame('US', $result->countryCode);
        $this->assertSame('United States', $result->geo?->countryName);
        $this->assertSame('NA', $result->geo?->continent);
    }

    public function test_it_maps_city_edition_record_with_coordinates(): void
    {
        $result = $this->mapper->map([
            'country' => [
                'iso_code' => 'DE',
                'names' => ['en' => 'Germany'],
            ],
            'city' => ['names' => ['en' => 'Berlin']],
            'subdivisions' => [
                ['names' => ['en' => 'Berlin']],
            ],
            'location' => [
                'latitude' => 52.52,
                'longitude' => 13.405,
            ],
        ], 'city');

        $this->assertTrue($result->isHit());
        $this->assertSame('DE', $result->countryCode);
        $this->assertSame('Berlin', $result->geo?->city);
        $this->assertSame('Berlin', $result->geo?->region);
        $this->assertSame(52.52, $result->geo?->latitude);
        $this->assertSame(13.405, $result->geo?->longitude);
    }

    public function test_it_maps_asn_only_record_without_country(): void
    {
        $result = $this->mapper->map([
            'autonomous_system_number' => 15169,
            'autonomous_system_organization' => 'Google LLC',
        ], 'asn');

        $this->assertSame(ProviderStatus::Hit, $result->status);
        $this->assertNull($result->countryCode);
        $this->assertSame(15169, $result->geo?->autonomousSystemNumber);
        $this->assertSame('Google LLC', $result->geo?->autonomousSystemOrganization);
    }
}
