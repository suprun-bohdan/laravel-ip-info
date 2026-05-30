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

        $lower = strtolower($input);

        if (str_starts_with($lower, '::ffff:')) {
            $ipv4 = substr($input, 7);

            if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ipv4;
            }
        }

        return $input;
    }
}
