<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SuprunBohdan\IpInfo\LocationDb\LocationDbCatalog;
use SuprunBohdan\IpInfo\LocationDb\LocationDbDownloader;
use Throwable;

final class UpdateLocationDbCommand extends Command
{
    protected $signature = 'ip-info:update-location-db
                            {--edition= : country or city edition to download}
                            {--force : Re-download even if MMDB files already exist}';

    protected $description = 'Download offline ip-location-db MMDB files (IPv4 + IPv6).';

    public function handle(LocationDbCatalog $catalog, LocationDbDownloader $downloader): int
    {
        $edition = (string) ($this->option('edition') ?: $catalog->edition());

        if (! $catalog->isDownloadEdition($edition)) {
            $this->error('Edition must be one of: country, city, asn_country, asn.');

            return self::FAILURE;
        }

        $this->info("Updating location DB edition [{$edition}]...");

        try {
            $report = $downloader->download($edition, (bool) $this->option('force'));

            foreach ($report->downloaded as $file) {
                $this->line('Downloaded: '.$file);
            }

            foreach ($report->notModified as $file) {
                $this->line('Not modified: '.$file);
            }

            if (! $report->hasChanges()) {
                $this->info('MMDB files already present. Use --force to re-download.');
            }

            $this->info('Location DB update complete.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Location DB update failed: '.$exception->getMessage());
            Log::error('ip-info:update-location-db failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
