<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\MaxMind;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Data\ProviderStatus;

final class MaxMindRecordMapper
{
    /**
     * @param  array<string, mixed>  $record
     */
    public function map(array $record, string $edition = 'country'): ProviderResult
    {
        $geo = $this->mapGeoLocation($record, $edition);
        $countryCode = $geo->countryCode;

        if ($countryCode !== null && $countryCode !== '') {
            return ProviderResult::hit($countryCode, 'maxmind', $geo);
        }

        if ($geo->autonomousSystemNumber !== null || $geo->autonomousSystemOrganization !== null) {
            return new ProviderResult(null, 'maxmind', ProviderStatus::Hit, $geo);
        }

        return ProviderResult::miss('maxmind');
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function mapGeoLocation(array $record, string $edition): GeoLocation
    {
        $countryCode = $this->stringOrNull($record['country']['iso_code'] ?? $record['country_code'] ?? null);
        $countryCode = $countryCode !== null ? strtoupper($countryCode) : null;

        $countryName = null;

        if (isset($record['country']) && is_array($record['country'])) {
            $countryName = $this->stringOrNull($record['country']['names']['en'] ?? null);
        }

        $continent = null;

        if (isset($record['continent']) && is_array($record['continent'])) {
            $continentCode = $this->stringOrNull($record['continent']['code'] ?? null);
            $continent = $continentCode !== null ? strtoupper($continentCode) : null;
        }

        $isEu = isset($record['country']['is_in_european_union'])
            ? (bool) $record['country']['is_in_european_union']
            : null;

        $city = null;
        $region = null;
        $latitude = null;
        $longitude = null;

        if ($edition === 'city' || isset($record['city']) || isset($record['location'])) {
            if (isset($record['city']) && is_array($record['city'])) {
                $city = $this->stringOrNull($record['city']['names']['en'] ?? null);
            }

            if (isset($record['subdivisions'][0]) && is_array($record['subdivisions'][0])) {
                $region = $this->stringOrNull($record['subdivisions'][0]['names']['en'] ?? null);
            }

            if (isset($record['location']) && is_array($record['location'])) {
                $latitude = isset($record['location']['latitude']) && is_numeric($record['location']['latitude'])
                    ? (float) $record['location']['latitude']
                    : null;
                $longitude = isset($record['location']['longitude']) && is_numeric($record['location']['longitude'])
                    ? (float) $record['location']['longitude']
                    : null;
            }
        }

        $asn = isset($record['autonomous_system_number']) && is_numeric($record['autonomous_system_number'])
            ? (int) $record['autonomous_system_number']
            : null;
        $aso = $this->stringOrNull($record['autonomous_system_organization'] ?? null);

        return new GeoLocation(
            countryCode: $countryCode,
            countryName: $countryName,
            continent: $continent,
            isEu: $isEu,
            city: $city,
            region: $region,
            latitude: $latitude,
            longitude: $longitude,
            autonomousSystemNumber: $asn,
            autonomousSystemOrganization: $aso,
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
