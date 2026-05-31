<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

use Illuminate\Support\Facades\Http;
use Throwable;

final class LocationDbDownloader
{
    public function __construct(private LocationDbCatalog $catalog) {}

    /**
     * @return list<string> downloaded file paths
     */
    public function download(string $edition, bool $force = false): array
    {
        if (! $this->catalog->isDownloadEdition($edition)) {
            throw new \InvalidArgumentException("Unsupported location DB edition [{$edition}].");
        }

        $storageDir = $this->catalog->storageDir();

        if (! is_dir($storageDir) && ! mkdir($storageDir, 0755, true) && ! is_dir($storageDir)) {
            throw new \RuntimeException("Unable to create location DB directory [{$storageDir}].");
        }

        $downloaded = [];

        foreach ($this->catalog->ipVersions() as $ipVersion) {
            $target = $this->catalog->filePath($edition, $ipVersion);

            if (! $force && is_readable($target)) {
                continue;
            }

            $url = $this->catalog->downloadUrl($edition, $ipVersion);

            if ($url === null) {
                throw new \RuntimeException("No download URL configured for [{$edition}/{$ipVersion}].");
            }

            $response = Http::timeout(300)->get($url);

            if (! $response->ok()) {
                throw new \RuntimeException("Failed to download [{$url}] with HTTP {$response->status()}.");
            }

            $temp = $target.'.tmp';
            file_put_contents($temp, $response->body());
            rename($temp, $target);
            $downloaded[] = $target;
        }

        $this->writeMetadata($edition, $downloaded);

        return $downloaded;
    }

    /**
     * @param  list<string>  $downloaded
     */
    private function writeMetadata(string $edition, array $downloaded): void
    {
        $metadata = [
            'edition' => $edition,
            'source' => $this->catalog->source(),
            'installed_at' => now()->toIso8601String(),
            'files' => array_map(
                static fn (string $path): array => [
                    'path' => $path,
                    'size' => filesize($path),
                    'modified_at' => date(DATE_ATOM, (int) filemtime($path)),
                ],
                $downloaded !== []
                    ? $downloaded
                    : array_map(
                        fn (string $version): string => $this->catalog->filePath($edition, $version),
                        $this->catalog->ipVersions(),
                    ),
            ),
        ];

        file_put_contents(
            $this->catalog->metadataPath(),
            json_encode($metadata, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );
    }
}
