<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Contracts\SchemaInspector;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Laravel\Models\IpCountry;
use SuprunBohdan\IpInfo\Support\IpRange;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class DatabaseRangeProvider implements IpProvider
{
    private ?bool $tableExists = null;

    public function __construct(
        private IpValidator $validator,
        private SchemaInspector $schema,
    ) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.database.enabled', false)) {
            return new ProviderResult(null, 'database', false);
        }

        if (! $this->validator->isIpv4($ip->value)) {
            return new ProviderResult(null, 'database', false);
        }

        if (! $this->hasTable()) {
            return new ProviderResult(null, 'database', false);
        }

        $ipLong = IpRange::ipv4ToLong($ip->value);

        $result = IpCountry::query()
            ->where('first_ip', '<=', $ipLong)
            ->where('last_ip', '>=', $ipLong)
            ->value('country');

        if (is_string($result) && $result !== '') {
            return new ProviderResult(strtoupper($result), 'database', true);
        }

        return new ProviderResult(null, 'database', false);
    }

    /**
     * @param  list<IpAddress>  $addresses
     * @return array<string, ProviderResult>
     */
    public function lookupMany(array $addresses): array
    {
        $results = [];

        foreach ($addresses as $address) {
            $results[$address->value] = $this->lookup($address);
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
