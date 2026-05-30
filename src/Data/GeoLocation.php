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
    ) {}

    public static function fromCountryCode(?string $countryCode): self
    {
        return new self($countryCode !== null ? strtoupper($countryCode) : null);
    }
}
