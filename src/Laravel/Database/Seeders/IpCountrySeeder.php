<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SuprunBohdan\IpInfo\Laravel\Models\IpCountry;
use SuprunBohdan\IpInfo\Laravel\Support\CsvFilePathService;
use SuprunBohdan\IpInfo\Support\IpRange;
use Throwable;

final class IpCountrySeeder extends Seeder
{
    public function __construct(private CsvFilePathService $csvFilePathService) {}

    public function run(): void
    {
        $csvFilePath = $this->csvFilePathService->getCsvFilePath();

        if (! is_readable($csvFilePath)) {
            Log::error("ip-info seeder: unreadable CSV at {$csvFilePath}");

            return;
        }

        $handle = fopen($csvFilePath, 'r');

        if ($handle === false) {
            Log::error("ip-info seeder: unable to open CSV at {$csvFilePath}");

            return;
        }

        if ($this->command !== null) {
            $this->command->info('Seeding ip_country from CSV...');
        }

        try {
            DB::transaction(function () use ($handle) {
                $batch = [];
                $batchSize = 1000;
                $imported = 0;

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if (count($data) < 3) {
                        continue;
                    }

                    [$firstIp, $lastIp, $country] = $data;

                    $batch[] = [
                        'first_ip' => IpRange::ipv4ToLong($firstIp),
                        'last_ip' => IpRange::ipv4ToLong($lastIp),
                        'country' => $country,
                    ];

                    if (count($batch) >= $batchSize) {
                        IpCountry::insertOrIgnore($batch);
                        $imported += count($batch);

                        if ($this->command !== null) {
                            $this->command->info("Imported {$imported} rows...");
                        }

                        $batch = [];
                    }
                }

                if ($batch !== []) {
                    IpCountry::insertOrIgnore($batch);
                    $imported += count($batch);
                }

                if ($this->command !== null) {
                    $this->command->info("Seeding complete. Imported {$imported} rows.");
                }
            });
        } catch (Throwable $exception) {
            Log::error('ip-info seeder failed: '.$exception->getMessage());
        } finally {
            fclose($handle);
        }
    }
}
