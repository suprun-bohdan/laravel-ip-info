<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

use SuprunBohdan\IpInfo\Exceptions\InvalidIpAddressException;

final class IpNormalizer
{
    public function normalize(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            throw new InvalidIpAddressException('IP address cannot be empty.');
        }

        if (str_contains($input, ',')) {
            throw new InvalidIpAddressException('Multiple addresses in one string are not allowed.');
        }

        if (str_contains($input, '%')) {
            $input = explode('%', $input, 2)[0];
        }

        $lower = strtolower($input);

        if (str_starts_with($lower, '::ffff:')) {
            $ipv4 = substr($input, 7);

            if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ipv4;
            }
        }

        if (filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->canonicalizeIpv6($input);
        }

        return $input;
    }

    public function anonymize(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);

            return implode('.', [$parts[0], $parts[1], $parts[2], '0']);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->anonymizeIpv6($this->normalize($ip));
        }

        return $ip;
    }

    private function canonicalizeIpv6(string $ip): string
    {
        $packed = inet_pton($ip);

        if ($packed === false) {
            return strtolower($ip);
        }

        $canonical = inet_ntop($packed);

        if ($canonical === false) {
            return strtolower($ip);
        }

        return strtolower($canonical);
    }

    private function anonymizeIpv6(string $ip): string
    {
        $packed = inet_pton($ip);

        if ($packed === false) {
            return $ip;
        }

        for ($index = 6; $index < 16; $index++) {
            $packed[$index] = "\0";
        }

        $anonymized = inet_ntop($packed);

        return $anonymized === false ? $ip : strtolower($anonymized);
    }
}
