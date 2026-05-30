<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

interface WhoisClient
{
    public function query(string $server, string $query, int $timeout): string;
}
