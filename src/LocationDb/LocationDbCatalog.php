<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

final class LocationDbCatalog
{
    /** @var list<string> */
    public const PRIMARY_EDITIONS = ['country', 'city', 'asn_country'];

    /** @var list<string> */
    public const DOWNLOAD_EDITIONS = ['country', 'city', 'asn_country', 'asn'];

    /**
     * @return list<string>
     */
    public function ipVersions(): array
    {
        return ['ipv4', 'ipv6'];
    }

    public function storageDir(): string
    {
        return (string) config('ip-info.location_db.storage_dir', storage_path('app/ip-info/location-db'));
    }

    public function edition(): string
    {
        $edition = (string) config('ip-info.location_db.edition', 'country');

        return in_array($edition, self::PRIMARY_EDITIONS, true) ? $edition : 'country';
    }

    public function isPrimaryEdition(string $edition): bool
    {
        return in_array($edition, self::PRIMARY_EDITIONS, true);
    }

    public function isDownloadEdition(string $edition): bool
    {
        return in_array($edition, self::DOWNLOAD_EDITIONS, true);
    }

    public function asnEditionInstalled(): bool
    {
        return $this->isInstalled('asn');
    }

    public function source(): string
    {
        return (string) config('ip-info.location_db.source', 'dbip');
    }

    public function filePath(string $edition, string $ipVersion): string
    {
        return $this->storageDir().DIRECTORY_SEPARATOR.$edition.'-'.$ipVersion.'.mmdb';
    }

    public function metadataPath(): string
    {
        return $this->storageDir().DIRECTORY_SEPARATOR.'metadata.json';
    }

    public function downloadUrl(string $edition, string $ipVersion): ?string
    {
        $url = $this->resolveDownloadUrl($this->source(), $edition, $ipVersion);

        if ($url !== null) {
            return $url;
        }

        if (in_array($edition, ['asn', 'asn_country'], true)) {
            return $this->resolveDownloadUrl('routeviews', $edition, $ipVersion);
        }

        return null;
    }

    public function isInstalled(string $edition): bool
    {
        foreach ($this->ipVersions() as $version) {
            if (! is_readable($this->filePath($edition, $version))) {
                return false;
            }
        }

        return true;
    }

    private function resolveDownloadUrl(string $source, string $edition, string $ipVersion): ?string
    {
        $sources = config('ip-info.location_db.sources', []);

        if (! is_array($sources)) {
            return null;
        }

        $editionUrls = $sources[$source][$edition] ?? null;

        if (! is_array($editionUrls)) {
            return null;
        }

        $url = $editionUrls[$ipVersion] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }
}
