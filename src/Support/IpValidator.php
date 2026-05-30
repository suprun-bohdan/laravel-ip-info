<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

final class IpValidator
{
    /** @var list<string> */
    private const IPV4_DOCUMENTATION_CIDRS = [
        '192.0.2.0/24',
        '198.51.100.0/24',
        '203.0.113.0/24',
    ];

    public function isValid(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public function isIpv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isIpv6(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function isLocalhost(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '0.0.0.0', '::1'], true);
    }

    public function isPrivate(string $ip): bool
    {
        if (! $this->isValid($ip)) {
            return false;
        }

        if ($this->isLocalhost($ip)) {
            return true;
        }

        if ($this->isIpv4($ip)) {
            return filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE
            ) === false;
        }

        return $this->isIpv6UniqueLocal($ip) || $this->isIpv6LinkLocal($ip);
    }

    public function isPublic(string $ip): bool
    {
        if (! $this->isValid($ip)) {
            return false;
        }

        return ! $this->shouldSkipExternalLookup($ip);
    }

    public function isLinkLocal(string $ip): bool
    {
        if ($this->isIpv4($ip)) {
            return IpRange::ipv4InCidr($ip, '169.254.0.0/16');
        }

        if ($this->isIpv6($ip)) {
            return $this->isIpv6LinkLocal($ip);
        }

        return false;
    }

    public function isReserved(string $ip): bool
    {
        if (! $this->isValid($ip)) {
            return false;
        }

        if ($this->isIpv4($ip)) {
            if ($this->isIpv4DocumentationNetwork($ip)) {
                return true;
            }

            return filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE
            ) === false && ! $this->isPrivate($ip);
        }

        if ($this->isIpv6($ip)) {
            return $this->isIpv6Unspecified($ip) || $this->isIpv6Multicast($ip);
        }

        return false;
    }

    public function shouldSkipExternalLookup(string $ip): bool
    {
        return $this->isLocalhost($ip)
            || $this->isPrivate($ip)
            || $this->isLinkLocal($ip)
            || $this->isReserved($ip);
    }

    private function isIpv4DocumentationNetwork(string $ip): bool
    {
        foreach (self::IPV4_DOCUMENTATION_CIDRS as $cidr) {
            if (IpRange::ipv4InCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function isIpv6Unspecified(string $ip): bool
    {
        return $ip === '::';
    }

    private function isIpv6Multicast(string $ip): bool
    {
        $binary = inet_pton($ip);

        if ($binary === false) {
            return false;
        }

        return ord($binary[0]) === 0xFF;
    }

    private function isIpv6UniqueLocal(string $ip): bool
    {
        $binary = inet_pton($ip);

        if ($binary === false) {
            return false;
        }

        return (ord($binary[0]) & 0xFE) === 0xFC;
    }

    private function isIpv6LinkLocal(string $ip): bool
    {
        $binary = inet_pton($ip);

        if ($binary === false) {
            return false;
        }

        return ord($binary[0]) === 0xFE && (ord($binary[1]) & 0xC0) === 0x80;
    }
}
