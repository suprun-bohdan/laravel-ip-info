<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuprunBohdan\IpInfo\Laravel\Database\Seeders\IpCountrySeeder;
use SuprunBohdan\IpInfo\Laravel\Support\CsvFilePathService;
use Throwable;

final class UpdateDatabaseCommand extends Command
{
    protected $signature = 'ip-info:update-database {--force : Re-download the CSV even if it already exists}';

    protected $description = 'Update the offline IPv4 country database from the upstream CSV.';

    private const CSV_URL = 'https://cdn.jsdelivr.net/npm/@ip-location-db/asn-country/asn-country-ipv4.csv';

    public function __construct(private CsvFilePathService $csvFilePathService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Updating offline IPv4 country database...');

        try {
            $storageFilePath = $this->csvFilePathService->getCsvFilePath();
            $force = (bool) $this->option('force');

            if ($force || ! file_exists($storageFilePath)) {
                $this->info('Downloading CSV...');

                $response = Http::timeout(120)->get(self::CSV_URL);

                if (! $response->ok()) {
                    $this->error('Failed to download CSV file.');

                    return self::FAILURE;
                }

                $this->csvFilePathService->putCsvFile($response->body());
                $this->info('CSV downloaded.');
            } else {
                $this->info('CSV already exists. Use --force to re-download.');
            }

            $this->call('migrate');

            Artisan::call('db:seed', [
                '--class' => IpCountrySeeder::class,
            ]);

            $this->info('Database updated successfully.');
        } catch (Throwable $exception) {
            $this->error('Update failed: '.$exception->getMessage());
            Log::error('ip-info:update-database failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
