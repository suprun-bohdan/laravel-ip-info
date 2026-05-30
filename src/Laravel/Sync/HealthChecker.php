<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

use Illuminate\Support\Facades\Schema;
use SuprunBohdan\IpInfo\Laravel\Support\CsvFilePathService;

final class HealthChecker
{
    public function __construct(private CsvFilePathService $csvFilePathService) {}

    public function databaseIsStale(): bool
    {
        if (! config('ip-info.database.enabled', false)) {
            return false;
        }

        $path = $this->csvFilePathService->getCsvFilePath();
        $staleDays = (int) config('ip-info.database.stale_days', 30);

        if (! file_exists($path)) {
            return true;
        }

        $ageSeconds = time() - (int) filemtime($path);

        return $ageSeconds > ($staleDays * 86400);
    }

    public function maxmindIsStale(): bool
    {
        if (! config('ip-info.maxmind.enabled', false)) {
            return false;
        }

        $path = (string) config('ip-info.maxmind.database_path', '');

        if ($path === '' || ! file_exists($path)) {
            return true;
        }

        $staleDays = (int) config('ip-info.maxmind.stale_days', 30);
        $ageSeconds = time() - (int) filemtime($path);

        return $ageSeconds > ($staleDays * 86400);
    }

    public function maxmindIsReadable(): bool
    {
        if (! config('ip-info.maxmind.enabled', false)) {
            return false;
        }

        $path = (string) config('ip-info.maxmind.database_path', '');

        return $path !== '' && is_readable($path);
    }

    public function ipCountryTablePresent(): bool
    {
        return Schema::hasTable('ip_country');
    }

    public function isDataHealthy(): bool
    {
        $databaseStale = $this->databaseIsStale();
        $maxmindStale = $this->maxmindIsStale();
        $maxmindReadable = $this->maxmindIsReadable();

        return ! $databaseStale && ! $maxmindStale
            && (! config('ip-info.maxmind.enabled') || $maxmindReadable);
    }
}
