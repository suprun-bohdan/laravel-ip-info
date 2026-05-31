<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;

interface IpCache
{
    public function get(IpAddress $ip): ?string;

    public function put(IpAddress $ip, string $countryCode): void;

    public function getGeo(IpAddress $ip): ?GeoLocation;

    public function putGeo(IpAddress $ip, GeoLocation $geo): void;

    public function forget(IpAddress $ip): void;

    public function hasNegative(IpAddress $ip): bool;

    public function putNegative(IpAddress $ip): void;

    public function forgetNegative(IpAddress $ip): void;
}
