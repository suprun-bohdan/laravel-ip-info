<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

use SuprunBohdan\IpInfo\Data\GeoLocation;

final class MmdbRecordMapper
{
    /**
     * @param  array<string, mixed>  $record
     */
    public function map(array $record): GeoLocation
    {
        $fields = config('ip-info.location_db.fields', []);

        if (! is_array($fields)) {
            $fields = [];
        }

        $countryCode = $this->string($record, ['country_code', 'country', 'countryCode']);

        if ($countryCode !== null) {
            $countryCode = strtoupper($countryCode);
        }

        return new GeoLocation(
            countryCode: $countryCode,
            countryName: $this->include($fields, 'country')
                ? $this->string($record, ['country_name', 'countryName'])
                : null,
            continent: null,
            isEu: null,
            city: $this->include($fields, 'city')
                ? $this->string($record, ['city', 'city_name'])
                : null,
            region: $this->include($fields, 'region')
                ? $this->string($record, ['state1', 'state_prov', 'region', 'subdivision_1_iso_code'])
                : null,
            region2: $this->include($fields, 'region')
                ? $this->string($record, ['state2', 'subdivision_2_iso_code'])
                : null,
            postcode: $this->include($fields, 'postcode')
                ? $this->string($record, ['postcode', 'postal_code', 'postal'])
                : null,
            latitude: $this->include($fields, 'latitude')
                ? $this->float($record, ['latitude', 'lat'])
                : null,
            longitude: $this->include($fields, 'longitude')
                ? $this->float($record, ['longitude', 'lon', 'lng'])
                : null,
            timezone: $this->include($fields, 'timezone')
                ? $this->string($record, ['timezone', 'time_zone'])
                : null,
        );
    }

    /**
     * @param  list<string>  $fields
     */
    private function include(array $fields, string $field): bool
    {
        return $fields === [] || in_array($field, $fields, true);
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  list<string>  $keys
     */
    private function string(array $record, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $record[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  list<string>  $keys
     */
    private function float(array $record, array $keys): ?float
    {
        foreach ($keys as $key) {
            $value = $record[$key] ?? null;

            if (is_int($value) || is_float($value)) {
                return (float) $value;
            }

            if (is_string($value) && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
