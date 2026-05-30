<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Testing;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;

final class FakeIpProvider implements IpProvider
{
    /** @var list<string> */
    private array $lookups = [];

    /**
     * @param  array<string, string|null>  $map
     */
    public function __construct(private array $map = []) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        $this->lookups[] = $ip->value;

        if (! array_key_exists($ip->value, $this->map)) {
            return new ProviderResult(null, 'fake', false);
        }

        $countryCode = $this->map[$ip->value];

        return new ProviderResult(
            $countryCode,
            'fake',
            true,
            $countryCode !== null ? new GeoLocation($countryCode) : null,
        );
    }

    /**
     * @return list<string>
     */
    public function lookups(): array
    {
        return $this->lookups;
    }

    public function assertLookedUp(string $ip): void
    {
        if (! in_array($ip, $this->lookups, true)) {
            throw new \RuntimeException(
                "Expected IP [{$ip}] to be looked up. Lookups: ".implode(', ', $this->lookups)
            );
        }
    }
}
