<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Laravel\Support\CsvFilePathService;

final class DiagnoseIpCommand extends Command
{
    protected $signature = 'ip-info:diagnose
                            {ip? : IP address to inspect}
                            {--json : Output diagnostics as JSON}';

    protected $description = 'Show package configuration and optional IP diagnostics.';

    public function __construct(private CsvFilePathService $csvFilePathService)
    {
        parent::__construct();
    }

    public function handle(IpInfoManager $ipInfo): int
    {
        $databaseStale = $this->databaseIsStale();
        $settings = [
            'cache.enabled' => (bool) config('ip-info.cache.enabled'),
            'cache.store' => config('ip-info.cache.store') ?? 'default',
            'cache.prefix' => config('ip-info.cache.prefix'),
            'cache.negative_ttl' => (int) config('ip-info.cache.negative_ttl', 300),
            'database.enabled' => (bool) config('ip-info.database.enabled'),
            'database.stale' => $databaseStale,
            'maxmind.enabled' => (bool) config('ip-info.maxmind.enabled'),
            'http.enabled' => (bool) config('ip-info.http.enabled'),
            'cleantalk.enabled' => (bool) config('ip-info.cleantalk.enabled'),
            'routes.enabled' => (bool) config('ip-info.routes.enabled'),
            'ip_country_table' => Schema::hasTable('ip_country') ? 'present' : 'missing',
        ];

        $ip = $this->argument('ip');
        $lookup = null;

        if (is_string($ip) && $ip !== '') {
            $lookup = $ipInfo->for($ip)->result()->toArray();
        }

        $healthy = ! $databaseStale;

        if ($this->option('json')) {
            $this->line(json_encode([
                'package' => 'suprun-bohdan/laravel-ip-info',
                'healthy' => $healthy,
                'settings' => $settings,
                'lookup' => $lookup,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->line('Laravel IP Info diagnostics');
        $this->newLine();

        $this->table(['Setting', 'Value'], collect($settings)->map(
            fn (mixed $value, string $key): array => [$key, is_bool($value) ? ($value ? 'true' : 'false') : (string) $value]
        )->values()->all());

        if ($databaseStale) {
            $this->warn('Offline database CSV is missing or stale. Run ip-info:update-database.');
        }

        if ($lookup !== null) {
            $this->newLine();
            $this->table(['Field', 'Value'], collect($lookup)->map(
                fn (mixed $value, string $key): array => [$key, $value === null ? 'null' : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value)]
            )->values()->all());
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function databaseIsStale(): bool
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
}
