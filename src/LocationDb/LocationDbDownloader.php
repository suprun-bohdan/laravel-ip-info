<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class LocationDbDownloader
{
    public function __construct(private LocationDbCatalog $catalog) {}

    public function download(string $edition, bool $force = false): LocationDbDownloadReport
    {
        if (! $this->catalog->isDownloadEdition($edition)) {
            throw new \InvalidArgumentException("Unsupported location DB edition [{$edition}].");
        }

        $storageDir = $this->catalog->storageDir();

        if (! is_dir($storageDir) && ! mkdir($storageDir, 0755, true) && ! is_dir($storageDir)) {
            throw new \RuntimeException("Unable to create location DB directory [{$storageDir}].");
        }

        $report = new LocationDbDownloadReport;

        foreach ($this->catalog->ipVersions() as $ipVersion) {
            $outcome = $this->downloadFile($edition, $ipVersion, $force);

            if ($outcome === 'downloaded') {
                $report = new LocationDbDownloadReport(
                    downloaded: [...$report->downloaded, $this->catalog->filePath($edition, $ipVersion)],
                    notModified: $report->notModified,
                );
            }

            if ($outcome === 'not_modified') {
                $report = new LocationDbDownloadReport(
                    downloaded: $report->downloaded,
                    notModified: [...$report->notModified, $this->catalog->filePath($edition, $ipVersion)],
                );
            }
        }

        $this->persistMetadata($edition);

        return $report;
    }

    private function downloadFile(string $edition, string $ipVersion, bool $force): string
    {
        $target = $this->catalog->filePath($edition, $ipVersion);
        $url = $this->catalog->downloadUrl($edition, $ipVersion);

        if ($url === null) {
            throw new \RuntimeException("No download URL configured for [{$edition}/{$ipVersion}].");
        }

        if (! $force && ! is_readable($target)) {
            return $this->storeResponse($target, $edition, $ipVersion, $url, Http::timeout(300)->get($url));
        }

        if ($force) {
            return $this->storeResponse($target, $edition, $ipVersion, $url, Http::timeout(300)->get($url));
        }

        $stored = $this->catalog->metadataForFile($edition, $ipVersion);
        $headers = [];

        if ($stored !== null) {
            if (isset($stored['etag']) && is_string($stored['etag']) && $stored['etag'] !== '') {
                $headers['If-None-Match'] = $stored['etag'];
            }

            if (isset($stored['last_modified']) && is_string($stored['last_modified']) && $stored['last_modified'] !== '') {
                $headers['If-Modified-Since'] = $stored['last_modified'];
            }
        }

        $response = $headers === []
            ? Http::timeout(300)->get($url)
            : Http::timeout(300)->withHeaders($headers)->get($url);

        if ($response->status() === 304) {
            $this->touchMetadata($edition, $ipVersion, $url, $stored ?? []);

            return 'not_modified';
        }

        if (! $response->ok()) {
            throw new \RuntimeException("Failed to download [{$url}] with HTTP {$response->status()}.");
        }

        return $this->storeResponse($target, $edition, $ipVersion, $url, $response);
    }

    private function storeResponse(
        string $target,
        string $edition,
        string $ipVersion,
        string $url,
        Response $response,
    ): string {
        if (! $response->ok()) {
            throw new \RuntimeException("Failed to download [{$url}] with HTTP {$response->status()}.");
        }

        $temp = $target.'.tmp';
        file_put_contents($temp, $response->body());
        rename($temp, $target);

        $this->touchMetadata($edition, $ipVersion, $url, [
            'etag' => $this->headerValue($response, 'ETag'),
            'last_modified' => $this->headerValue($response, 'Last-Modified'),
        ]);

        return 'downloaded';
    }

    /**
     * @param  array<string, mixed>  $stored
     */
    private function touchMetadata(string $edition, string $ipVersion, string $url, array $stored): void
    {
        $target = $this->catalog->filePath($edition, $ipVersion);
        $metadata = $this->catalog->readMetadata() ?? ['files' => []];
        $files = is_array($metadata['files'] ?? null) ? $metadata['files'] : [];

        $entry = [
            'edition' => $edition,
            'ip_version' => $ipVersion,
            'path' => $target,
            'url' => $url,
            'etag' => $stored['etag'] ?? null,
            'last_modified' => $stored['last_modified'] ?? null,
            'size' => is_readable($target) ? filesize($target) : null,
            'modified_at' => is_readable($target) ? date(DATE_ATOM, (int) filemtime($target)) : null,
            'checked_at' => now()->toIso8601String(),
        ];

        $files = $this->upsertFileMetadata($files, $entry);
        $metadata['files'] = $files;
        $metadata['edition'] = $edition;
        $metadata['source'] = $this->catalog->source();
        $metadata['installed_at'] = $metadata['installed_at'] ?? now()->toIso8601String();

        file_put_contents(
            $this->catalog->metadataPath(),
            json_encode($metadata, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );
    }

    private function persistMetadata(string $edition): void
    {
        foreach ($this->catalog->ipVersions() as $ipVersion) {
            $target = $this->catalog->filePath($edition, $ipVersion);

            if (! is_readable($target)) {
                continue;
            }

            $stored = $this->catalog->metadataForFile($edition, $ipVersion) ?? [];
            $url = $this->catalog->downloadUrl($edition, $ipVersion) ?? ($stored['url'] ?? null);

            if (! is_string($url) || $url === '') {
                continue;
            }

            $this->touchMetadata($edition, $ipVersion, $url, $stored);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $files
     * @param  array<string, mixed>  $entry
     * @return list<array<string, mixed>>
     */
    private function upsertFileMetadata(array $files, array $entry): array
    {
        $updated = [];

        foreach ($files as $file) {
            if (($file['edition'] ?? null) === $entry['edition']
                && ($file['ip_version'] ?? null) === $entry['ip_version']) {
                continue;
            }

            $updated[] = $file;
        }

        $updated[] = $entry;

        return $updated;
    }

    private function headerValue(Response $response, string $header): ?string
    {
        /** @var mixed $value */
        $value = $response->header($header);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
