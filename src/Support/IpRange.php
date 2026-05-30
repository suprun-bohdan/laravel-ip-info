<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

final class IpRange
{
    public static function ipv4InCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    public static function ipv4ToLong(string $ip): int
    {
        $long = ip2long($ip);

        if ($long === false) {
            throw new \InvalidArgumentException("Invalid IPv4 address: {$ip}");
        }

        return $long;
    }
}
