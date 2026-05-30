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

    /** @var list<string|null> */
    private array $sequence = [];

    private int $sequenceIndex = 0;

    /**
     * @param  array<string, string|null>  $map
     * @param  list<string|null>  $sequence
     */
    public function __construct(private array $map = [], array $sequence = [])
    {
        $this->sequence = $sequence;
    }

    public function lookup(IpAddress $ip): ProviderResult
    {
        $this->lookups[] = $ip->value;

        if ($this->sequence !== [] && isset($this->sequence[$this->sequenceIndex])) {
            $countryCode = $this->sequence[$this->sequenceIndex];
            $this->sequenceIndex++;

            return $this->makeResult($countryCode);
        }

        if (! array_key_exists($ip->value, $this->map)) {
            return ProviderResult::skipped('fake');
        }

        return $this->makeResult($this->map[$ip->value]);
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

    private function makeResult(?string $countryCode): ProviderResult
    {
        if ($countryCode === null || $countryCode === '') {
            return ProviderResult::miss('fake');
        }

        return ProviderResult::hit(
            $countryCode,
            'fake',
            new GeoLocation($countryCode),
        );
    }
}
