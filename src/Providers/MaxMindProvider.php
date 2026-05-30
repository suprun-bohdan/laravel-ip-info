<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use MaxMind\Db\Reader;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class MaxMindProvider implements IpProvider
{
    public function __construct(private IpValidator $validator) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.maxmind.enabled', false)) {
            return new ProviderResult(null, 'maxmind', false);
        }

        if (! $this->validator->isPublic($ip->value)) {
            return new ProviderResult(null, 'maxmind', false);
        }

        if (! class_exists(Reader::class)) {
            return new ProviderResult(null, 'maxmind', false);
        }

        $path = (string) config('ip-info.maxmind.database_path', '');

        if ($path === '' || ! is_readable($path)) {
            return new ProviderResult(null, 'maxmind', false);
        }

        try {
            $reader = new Reader($path);
            /** @var array<string, mixed>|null $record */
            $record = $reader->get($ip->value);
            $reader->close();

            if (! is_array($record)) {
                return new ProviderResult(null, 'maxmind', true);
            }

            $countryCode = isset($record['country']) && is_array($record['country'])
                ? ($record['country']['iso_code'] ?? null)
                : ($record['country_code'] ?? null);

            if (! is_string($countryCode) || $countryCode === '') {
                return new ProviderResult(null, 'maxmind', true);
            }

            $countryCode = strtoupper($countryCode);
            $continent = null;

            if (isset($record['continent']) && is_array($record['continent'])) {
                $continent = $record['continent']['code'] ?? null;
                $continent = is_string($continent) ? strtoupper($continent) : null;
            }

            $geo = new GeoLocation(
                $countryCode,
                is_string($record['country']['names']['en'] ?? null) ? $record['country']['names']['en'] : null,
                $continent,
                isset($record['country']['is_in_european_union'])
                    ? (bool) $record['country']['is_in_european_union']
                    : null,
            );

            return new ProviderResult($countryCode, 'maxmind', true, $geo);
        } catch (\Throwable) {
            return new ProviderResult(null, 'maxmind', false);
        }
    }
}
