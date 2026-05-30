<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Support;

use SuprunBohdan\IpInfo\Contracts\ReverseDnsResolver;

final class FakeReverseDnsResolver implements ReverseDnsResolver
{
    /** @var array<string, string|null> */
    private array $addrToHost = [];

    /** @var array<string, string|null> */
    private array $hostToAddr = [];

    public function register(string $ip, ?string $hostname, ?string $forwardIp = null): void
    {
        $this->addrToHost[$ip] = $hostname;

        if ($hostname !== null) {
            $this->hostToAddr[$hostname] = $forwardIp ?? $ip;
        }
    }

    public function getHostByAddr(string $ip): ?string
    {
        return $this->addrToHost[$ip] ?? null;
    }

    public function getHostByName(string $hostname): ?string
    {
        return $this->hostToAddr[$hostname] ?? null;
    }
}
