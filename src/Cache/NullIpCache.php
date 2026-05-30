<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Cache;

use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Data\IpAddress;

final class NullIpCache implements IpCache
{
    public function get(IpAddress $ip): ?string
    {
        return null;
    }

    public function put(IpAddress $ip, string $countryCode): void {}

    public function forget(IpAddress $ip): void {}

    public function hasNegative(IpAddress $ip): bool
    {
        return false;
    }

    public function putNegative(IpAddress $ip): void {}

    public function forgetNegative(IpAddress $ip): void {}
}
