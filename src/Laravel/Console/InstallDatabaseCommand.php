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

final class InstallDatabaseCommand extends Command
{
    protected $signature = 'ip-info:install-database';

    protected $description = 'Download IPv4 country CSV, run migrations, and seed the offline database.';

    private const CSV_URL = 'https://cdn.jsdelivr.net/npm/@ip-location-db/asn-country/asn-country-ipv4.csv';

    public function __construct(private CsvFilePathService $csvFilePathService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Installing offline IPv4 country database...');

        try {
            $storageFilePath = $this->csvFilePathService->getCsvFilePath();

            if (! file_exists($storageFilePath)) {
                $this->info('Downloading CSV...');

                $response = Http::timeout(120)->get(self::CSV_URL);

                if (! $response->ok()) {
                    $this->error('Failed to download CSV file.');

                    return self::FAILURE;
                }

                $this->csvFilePathService->putCsvFile($response->body());
                $this->info('CSV downloaded.');
            } else {
                $this->info('CSV already exists, skipping download.');
            }

            $this->call('migrate');

            Artisan::call('db:seed', [
                '--class' => IpCountrySeeder::class,
            ]);

            $this->info('Database seeded successfully.');
        } catch (Throwable $exception) {
            $this->error('Installation failed: '.$exception->getMessage());
            Log::error('ip-info:install-database failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Offline database installed.');

        return self::SUCCESS;
    }
}
