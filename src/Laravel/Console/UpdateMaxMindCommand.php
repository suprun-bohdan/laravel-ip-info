<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SuprunBohdan\IpInfo\MaxMind\MaxMindCatalog;
use Throwable;

final class UpdateMaxMindCommand extends Command
{
    protected $signature = 'ip-info:update-maxmind
                            {--edition=country : GeoLite2 edition (country, city, or asn)}
                            {--force : Re-download even if MMDB exists}';

    protected $description = 'Download GeoLite2 MMDB from MaxMind (country, city, or asn edition).';

    private const DOWNLOAD_URL = 'https://download.maxmind.com/app/geoip_download';

    public function __construct(private MaxMindCatalog $catalog)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $edition = $this->resolveEditionOption();

        if ($edition === null) {
            $this->error('Unsupported edition. Use country, city, or asn.');

            return self::FAILURE;
        }

        $licenseKey = (string) config('ip-info.maxmind.license_key', '');

        if ($licenseKey === '') {
            $this->error('Set IP_INFO_MAXMIND_LICENSE_KEY in .env or ip-info.maxmind.license_key.');

            return self::FAILURE;
        }

        $editionId = $this->catalog->editionId($edition);
        $relativePath = $this->resolveRelativePath($edition);
        $storagePath = Storage::path($relativePath);

        if (file_exists($storagePath) && ! $this->option('force')) {
            $this->info("MaxMind database already exists for edition [{$edition}]. Use --force to re-download.");

            return self::SUCCESS;
        }

        $this->info("Downloading {$editionId}...");

        try {
            $response = Http::timeout(120)->get(self::DOWNLOAD_URL, [
                'edition_id' => $editionId,
                'license_key' => $licenseKey,
                'suffix' => 'tar.gz',
            ]);

            if (! $response->ok()) {
                $this->error('MaxMind download failed with HTTP '.$response->status());

                return self::FAILURE;
            }

            $tmpArchive = tempnam(sys_get_temp_dir(), 'maxmind_').'.tar.gz';
            file_put_contents($tmpArchive, $response->body());

            $extractDir = sys_get_temp_dir().'/maxmind_'.uniqid();
            mkdir($extractDir);

            if (! class_exists(\PharData::class)) {
                $this->error('ext-phar is required to extract MaxMind archives.');

                return self::FAILURE;
            }

            $phar = new \PharData($tmpArchive);
            $tarPath = str_replace('.gz', '', $tmpArchive);
            $phar->decompress();
            (new \PharData($tarPath))->extractTo($extractDir);

            $expectedFilename = $this->catalog->databaseFilename($edition);
            $mmdb = $this->findMmdb($extractDir, $expectedFilename);

            if ($mmdb === null) {
                $this->error("{$expectedFilename} not found in archive.");

                return self::FAILURE;
            }

            Storage::makeDirectory(dirname($relativePath));
            copy($mmdb, $storagePath);

            @unlink($tmpArchive);
            @unlink($tarPath);
            $this->deleteDirectory($extractDir);

            $this->info("MaxMind database [{$edition}] installed at: {$storagePath}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('MaxMind update failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveEditionOption(): ?string
    {
        $option = $this->option('edition');

        if (! is_string($option) || $option === '') {
            return $this->catalog->edition();
        }

        return $this->catalog->isSupportedEdition($option) ? $option : null;
    }

    private function resolveRelativePath(string $edition): string
    {
        $configured = config('ip-info.maxmind.database_path');

        if (is_string($configured) && $configured !== '') {
            if (str_starts_with($configured, storage_path())) {
                return ltrim(str_replace(storage_path('app'), '', $configured), '/');
            }

            return $configured;
        }

        return 'geoip/'.$this->catalog->databaseFilename($edition);
    }

    private function findMmdb(string $directory, string $expectedFilename): ?string
    {
        $fallback = null;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.mmdb')) {
                continue;
            }

            if ($file->getFilename() === $expectedFilename) {
                return $file->getPathname();
            }

            $fallback ??= $file->getPathname();
        }

        return $fallback;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
