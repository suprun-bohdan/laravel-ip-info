<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class GeoLocationCachePayloadTest extends TestCase
{
    public function test_to_cache_payload_respects_field_whitelist(): void
    {
        config(['ip-info.location_db.fields' => ['country', 'city', 'timezone']]);

        $geo = new GeoLocation(
            countryCode: 'UA',
            countryName: 'Ukraine',
            city: 'Kyiv',
            region: '30',
            timezone: 'Europe/Kyiv',
            latitude: 50.45,
            longitude: 30.52,
        );

        $payload = $geo->toCachePayload();

        $this->assertSame('UA', $payload['cc']);
        $this->assertSame('Ukraine', $payload['cn']);
        $this->assertSame('Kyiv', $payload['city']);
        $this->assertSame('Europe/Kyiv', $payload['tz']);
        $this->assertArrayNotHasKey('region', $payload);
        $this->assertArrayNotHasKey('lat', $payload);
    }

    public function test_from_cache_payload_round_trips_geo_fields(): void
    {
        $geo = GeoLocation::fromCachePayload([
            'cc' => 'ua',
            'city' => 'Kyiv',
            'region' => '30',
            'tz' => 'Europe/Kyiv',
            'asn' => 15169,
            'aso' => 'GOOGLE',
        ]);

        $this->assertSame('UA', $geo->countryCode);
        $this->assertSame('Kyiv', $geo->city);
        $this->assertSame('30', $geo->region);
        $this->assertSame('Europe/Kyiv', $geo->timezone);
        $this->assertSame(15169, $geo->autonomousSystemNumber);
        $this->assertSame('GOOGLE', $geo->autonomousSystemOrganization);
    }
}
