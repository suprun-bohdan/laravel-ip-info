<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

final readonly class GeoLocation
{
    public function __construct(public ?string $countryCode) {}
}
