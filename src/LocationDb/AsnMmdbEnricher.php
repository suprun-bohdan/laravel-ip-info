<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;

final class AsnMmdbEnricher
{
    public function __construct(
        private LocationDbCatalog $catalog,
        private MmdbReaderPool $readers,
        private AsnMmdbRecordMapper $mapper,
    ) {}

    public function enrich(IpAddress $address, GeoLocation $geo): GeoLocation
    {
        if (! config('ip-info.location_db.enrich_asn', false)) {
            return $geo;
        }

        if (! $this->catalog->isInstalled('asn')) {
            return $geo;
        }

        if ($geo->autonomousSystemNumber !== null) {
            return $geo;
        }

        $record = $this->readers->lookup($address->value, 'asn');

        if ($record === null) {
            return $geo;
        }

        $asnGeo = $this->mapper->map($record);

        if ($asnGeo->autonomousSystemNumber === null && $asnGeo->autonomousSystemOrganization === null) {
            return $geo;
        }

        return new GeoLocation(
            countryCode: $geo->countryCode,
            countryName: $geo->countryName,
            continent: $geo->continent,
            isEu: $geo->isEu,
            city: $geo->city,
            region: $geo->region,
            region2: $geo->region2,
            postcode: $geo->postcode,
            latitude: $geo->latitude,
            longitude: $geo->longitude,
            timezone: $geo->timezone,
            autonomousSystemNumber: $asnGeo->autonomousSystemNumber,
            autonomousSystemOrganization: $asnGeo->autonomousSystemOrganization,
        );
    }
}
