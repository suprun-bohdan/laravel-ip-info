<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\MaxMind;

use Illuminate\Support\Facades\Storage;

final class MaxMindCatalog
{
    /** @var list<string> */
    public const EDITIONS = ['country', 'city', 'asn'];

    /** @var array<string, string> */
    private const EDITION_IDS = [
        'country' => 'GeoLite2-Country',
        'city' => 'GeoLite2-City',
        'asn' => 'GeoLite2-ASN',
    ];

    /** @var array<string, string> */
    private const EDITION_FILES = [
        'country' => 'GeoLite2-Country.mmdb',
        'city' => 'GeoLite2-City.mmdb',
        'asn' => 'GeoLite2-ASN.mmdb',
    ];

    public function edition(?string $override = null): string
    {
        $edition = $override ?? (string) config('ip-info.maxmind.edition', 'country');

        return in_array($edition, self::EDITIONS, true) ? $edition : 'country';
    }

    public function editionId(?string $edition = null): string
    {
        $edition ??= $this->edition();

        return self::EDITION_IDS[$edition];
    }

    public function databasePath(?string $edition = null): string
    {
        $configured = config('ip-info.maxmind.database_path');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $edition ??= $this->edition();

        return Storage::path('geoip/'.self::EDITION_FILES[$edition]);
    }

    public function databaseFilename(string $edition): string
    {
        return self::EDITION_FILES[$this->edition($edition)];
    }

    public function isSupportedEdition(string $edition): bool
    {
        return in_array($edition, self::EDITIONS, true);
    }
}
