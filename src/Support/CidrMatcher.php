<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

final class CidrMatcher
{
    /**
     * @param  list<string>  $cidrs
     */
    public static function matchesAny(string $ip, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if ($cidr !== '' && self::matches($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    public static function matches(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::matchIpv4($ip, $subnet, $bits);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::matchIpv6($ip, $subnet, $bits);
        }

        return false;
    }

    private static function matchIpv4(string $ip, string $subnet, int $bits): bool
    {
        if ($bits < 0 || $bits > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private static function matchIpv6(string $ip, string $subnet, int $bits): bool
    {
        if ($bits < 0 || $bits > 128) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainder)) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }
}
