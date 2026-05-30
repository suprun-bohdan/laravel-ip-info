<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

interface ReverseDnsResolver
{
    public function getHostByAddr(string $ip): ?string;

    public function getHostByName(string $hostname): ?string;
}
