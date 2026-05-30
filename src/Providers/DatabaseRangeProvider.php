<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use Illuminate\Support\Facades\Schema;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Laravel\Models\IpCountry;
use SuprunBohdan\IpInfo\Support\IpRange;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class DatabaseRangeProvider implements IpProvider
{
    private ?bool $tableExists = null;

    public function __construct(private IpValidator $validator) {}

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

    private function hasTable(): bool
    {
        if ($this->tableExists === null) {
            $this->tableExists = Schema::hasTable('ip_country');
        }

        return $this->tableExists;
    }
}
