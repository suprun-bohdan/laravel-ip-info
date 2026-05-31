<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

use SuprunBohdan\IpInfo\Data\GeoLocation;

final class AsnMmdbRecordMapper
{
    /**
     * @param  array<string, mixed>  $record
     */
    public function map(array $record): GeoLocation
    {
        $asn = $this->int($record, ['autonomous_system_number', 'asn', 'autonomousSystemNumber']);
        $organization = $this->string($record, [
            'autonomous_system_organization',
            'organization',
            'autonomousSystemOrganization',
        ]);

        return new GeoLocation(
            countryCode: null,
            autonomousSystemNumber: $asn,
            autonomousSystemOrganization: $organization,
        );
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
    private function int(array $record, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $record[$key] ?? null;

            if (is_int($value)) {
                return $value;
            }

            if (is_string($value) && ctype_digit($value)) {
                return (int) $value;
            }
        }

        return null;
    }
}
