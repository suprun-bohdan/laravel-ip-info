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
}
