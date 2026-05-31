<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Dns;

use SuprunBohdan\IpInfo\Contracts\ReverseDnsResolver;

final class SocketReverseDnsResolver implements ReverseDnsResolver
{
    public function getHostByAddr(string $ip): ?string
    {
        $hostname = @gethostbyaddr($ip);

        if ($hostname === false || $hostname === $ip) {
            return null;
        }

        return $hostname;
    }

    public function getHostByName(string $hostname): ?string
    {
        $ip = @gethostbyname($hostname);

        if ($ip === $hostname) {
            return null;
        }

        return $ip;
    }
}
