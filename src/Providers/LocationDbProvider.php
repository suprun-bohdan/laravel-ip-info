<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\LocationDb\MmdbReaderPool;
use SuprunBohdan\IpInfo\LocationDb\MmdbRecordMapper;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class LocationDbProvider implements IpProvider
{
    public function __construct(
        private IpValidator $validator,
        private MmdbReaderPool $readers,
        private MmdbRecordMapper $mapper,
    ) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.location_db.enabled', false)) {
            return ProviderResult::skipped('location_db');
        }

        if ($this->validator->shouldSkipExternalLookup($ip->value)) {
            return ProviderResult::miss('location_db', 'Private or reserved address.');
        }

        $record = $this->readers->lookup($ip->value);

        if ($record === null) {
            return ProviderResult::miss('location_db');
        }

        $geo = $this->mapper->map($record);
        $countryCode = $geo->countryCode;

        if ($countryCode === null || $countryCode === '') {
            return ProviderResult::miss('location_db');
        }

        return ProviderResult::hit($countryCode, 'location_db', $geo);
    }
}
