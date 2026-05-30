<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\BatchIpProvider;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Contracts\SchemaInspector;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Laravel\Models\IpCountry;
use SuprunBohdan\IpInfo\Support\IpRange;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class DatabaseRangeProvider implements BatchIpProvider, IpProvider
{
    private ?bool $tableExists = null;

    public function __construct(
        private IpValidator $validator,
        private SchemaInspector $schema,
    ) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        $results = $this->lookupMany([$ip]);

        return $results[$ip->value] ?? ProviderResult::skipped('database');
    }

    /**
     * @param  list<IpAddress>  $addresses
     * @return array<string, ProviderResult>
     */
    public function lookupMany(array $addresses): array
    {
        $results = [];

        if (! config('ip-info.database.enabled', false) || ! $this->hasTable()) {
            foreach ($addresses as $address) {
                $results[$address->value] = ProviderResult::skipped('database');
            }

            return $results;
        }

        $longs = [];

        foreach ($addresses as $address) {
            if (! $this->validator->isIpv4($address->value)) {
                $results[$address->value] = ProviderResult::skipped('database');

                continue;
            }

            $longs[$address->value] = IpRange::ipv4ToLong($address->value);
        }

        if ($longs === []) {
            return $results;
        }

        $minLong = min($longs);
        $maxLong = max($longs);

        $rows = IpCountry::query()
            ->where('first_ip', '<=', $maxLong)
            ->where('last_ip', '>=', $minLong)
            ->get(['first_ip', 'last_ip', 'country']);

        foreach ($longs as $ip => $long) {
            $country = null;

            foreach ($rows as $row) {
                if ($row->first_ip <= $long && $row->last_ip >= $long) {
                    $country = $row->country;
                    break;
                }
            }

            if (is_string($country) && $country !== '') {
                $results[$ip] = ProviderResult::hit(strtoupper($country), 'database');
            } else {
                $results[$ip] = ProviderResult::skipped('database');
            }
        }

        return $results;
    }

    private function hasTable(): bool
    {
        if ($this->tableExists === null) {
            $this->tableExists = $this->schema->hasTable('ip_country');
        }

        return $this->tableExists;
    }
}
