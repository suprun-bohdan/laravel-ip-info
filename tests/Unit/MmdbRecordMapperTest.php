<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\LocationDb\MmdbRecordMapper;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class MmdbRecordMapperTest extends TestCase
{
    public function test_it_maps_dbip_city_record_fields(): void
    {
        config(['ip-info.location_db.fields' => [
            'country', 'city', 'region', 'postcode', 'latitude', 'longitude', 'timezone',
        ]]);

        $mapper = $this->app->make(MmdbRecordMapper::class);

        $geo = $mapper->map([
            'country_code' => 'us',
            'city' => 'Mountain View',
            'state1' => 'California',
            'state2' => 'Santa Clara',
            'postcode' => '94043',
            'latitude' => 37.4056,
            'longitude' => -122.0775,
            'timezone' => 'America/Los_Angeles',
        ]);

        $this->assertSame('US', $geo->countryCode);
        $this->assertSame('Mountain View', $geo->city);
        $this->assertSame('California', $geo->region);
        $this->assertSame('Santa Clara', $geo->region2);
        $this->assertSame('94043', $geo->postcode);
        $this->assertSame(37.4056, $geo->latitude);
        $this->assertSame(-122.0775, $geo->longitude);
        $this->assertSame('America/Los_Angeles', $geo->timezone);
        $this->assertSame(['lat' => 37.4056, 'lon' => -122.0775], $geo->coordinates());
    }

    public function test_fields_whitelist_strips_excluded_values(): void
    {
        config(['ip-info.location_db.fields' => ['country']]);

        $mapper = $this->app->make(MmdbRecordMapper::class);

        $geo = $mapper->map([
            'country_code' => 'DE',
            'city' => 'Berlin',
            'latitude' => 52.52,
            'longitude' => 13.405,
        ]);

        $this->assertSame('DE', $geo->countryCode);
        $this->assertNull($geo->city);
        $this->assertNull($geo->latitude);
        $this->assertNull($geo->longitude);
        $this->assertNull($geo->coordinates());
    }

    public function test_it_accepts_alias_keys(): void
    {
        config(['ip-info.location_db.fields' => ['country', 'region', 'timezone']]);

        $mapper = $this->app->make(MmdbRecordMapper::class);

        $geo = $mapper->map([
            'country' => 'fr',
            'state_prov' => 'IDF',
            'time_zone' => 'Europe/Paris',
        ]);

        $this->assertSame('FR', $geo->countryCode);
        $this->assertSame('IDF', $geo->region);
        $this->assertSame('Europe/Paris', $geo->timezone);
    }
}
