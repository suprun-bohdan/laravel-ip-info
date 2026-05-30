<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Support;

use SuprunBohdan\IpInfo\Contracts\WhoisClient;

final class FakeWhoisClient implements WhoisClient
{
    /** @var array<string, array<string, string>> */
    private array $responses = [];

    public function register(string $server, string $query, string $response): self
    {
        $this->responses[strtolower($server)][strtolower(trim($query))] = $response;

        return $this;
    }

    public function query(string $server, string $query, int $timeout): string
    {
        $serverKey = strtolower($server);
        $queryKey = strtolower(trim($query));

        return $this->responses[$serverKey][$queryKey]
            ?? throw new \RuntimeException("Unexpected WHOIS query: {$server} / {$query}");
    }
}
