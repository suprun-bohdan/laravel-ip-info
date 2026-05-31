<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

final readonly class GeoLocation
{
    public function __construct(
        public ?string $countryCode,
        public ?string $countryName = null,
        public ?string $continent = null,
        public ?bool $isEu = null,
        public ?string $city = null,
        public ?string $region = null,
        public ?string $region2 = null,
        public ?string $postcode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $timezone = null,
        public ?int $autonomousSystemNumber = null,
        public ?string $autonomousSystemOrganization = null,
    ) {}

    public static function fromCountryCode(?string $countryCode): self
    {
        return new self($countryCode !== null ? strtoupper($countryCode) : null);
    }

    public function isEu(): bool
    {
        return $this->isEu === true;
    }

    public function isInContinent(string $continent): bool
    {
        if ($this->continent === null) {
            return false;
        }

        return strtoupper($this->continent) === strtoupper($continent);
    }

    public function countryNameOrCode(): ?string
    {
        if ($this->countryName !== null && $this->countryName !== '') {
            return $this->countryName;
        }

        return $this->countryCode;
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    public function coordinates(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return [
            'lat' => $this->latitude,
            'lon' => $this->longitude,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toCachePayload(): array
    {
        $fields = config('ip-info.location_db.fields', []);

        if (! is_array($fields)) {
            $fields = [];
        }

        $payload = [];

        if ($this->countryCode !== null) {
            $payload['cc'] = $this->countryCode;
        }

        if ($this->includeField($fields, 'country') && $this->countryName !== null) {
            $payload['cn'] = $this->countryName;
        }

        if ($this->continent !== null) {
            $payload['cont'] = $this->continent;
        }

        if ($this->isEu !== null) {
            $payload['eu'] = $this->isEu;
        }

        if ($this->includeField($fields, 'city') && $this->city !== null) {
            $payload['city'] = $this->city;
        }

        if ($this->includeField($fields, 'region') && $this->region !== null) {
            $payload['region'] = $this->region;
        }

        if ($this->includeField($fields, 'region') && $this->region2 !== null) {
            $payload['region2'] = $this->region2;
        }

        if ($this->includeField($fields, 'postcode') && $this->postcode !== null) {
            $payload['postcode'] = $this->postcode;
        }

        if ($this->includeField($fields, 'latitude') && $this->latitude !== null) {
            $payload['lat'] = $this->latitude;
        }

        if ($this->includeField($fields, 'longitude') && $this->longitude !== null) {
            $payload['lon'] = $this->longitude;
        }

        if ($this->includeField($fields, 'timezone') && $this->timezone !== null) {
            $payload['tz'] = $this->timezone;
        }

        if ($this->autonomousSystemNumber !== null) {
            $payload['asn'] = $this->autonomousSystemNumber;
        }

        if ($this->autonomousSystemOrganization !== null) {
            $payload['aso'] = $this->autonomousSystemOrganization;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromCachePayload(array $payload): self
    {
        $countryCode = isset($payload['cc']) && is_string($payload['cc'])
            ? strtoupper($payload['cc'])
            : null;

        return new self(
            countryCode: $countryCode,
            countryName: isset($payload['cn']) && is_string($payload['cn']) ? $payload['cn'] : null,
            continent: isset($payload['cont']) && is_string($payload['cont']) ? $payload['cont'] : null,
            isEu: array_key_exists('eu', $payload) ? (bool) $payload['eu'] : null,
            city: isset($payload['city']) && is_string($payload['city']) ? $payload['city'] : null,
            region: isset($payload['region']) && is_string($payload['region']) ? $payload['region'] : null,
            region2: isset($payload['region2']) && is_string($payload['region2']) ? $payload['region2'] : null,
            postcode: isset($payload['postcode']) && is_string($payload['postcode']) ? $payload['postcode'] : null,
            latitude: isset($payload['lat']) && is_numeric($payload['lat']) ? (float) $payload['lat'] : null,
            longitude: isset($payload['lon']) && is_numeric($payload['lon']) ? (float) $payload['lon'] : null,
            timezone: isset($payload['tz']) && is_string($payload['tz']) ? $payload['tz'] : null,
            autonomousSystemNumber: isset($payload['asn']) && is_numeric($payload['asn']) ? (int) $payload['asn'] : null,
            autonomousSystemOrganization: isset($payload['aso']) && is_string($payload['aso']) ? $payload['aso'] : null,
        );
    }

    /**
     * @param  list<string>  $fields
     */
    private function includeField(array $fields, string $field): bool
    {
        return $fields === [] || in_array($field, $fields, true);
    }
}
