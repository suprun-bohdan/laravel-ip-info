<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

final class LocationDbCatalog
{
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

        return in_array($edition, ['country', 'city'], true) ? $edition : 'country';
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
        $sources = config('ip-info.location_db.sources', []);

        if (! is_array($sources)) {
            return null;
        }

        $source = $this->source();
        $editionUrls = $sources[$source][$edition] ?? null;

        if (! is_array($editionUrls)) {
            return null;
        }

        $url = $editionUrls[$ipVersion] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
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
}
