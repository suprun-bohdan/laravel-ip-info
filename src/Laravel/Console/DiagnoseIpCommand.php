<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;

final class DiagnoseIpCommand extends Command
{
    protected $signature = 'ip-info:diagnose
                            {ip? : IP address to inspect}
                            {--json : Output diagnostics as JSON}';

    protected $description = 'Show package configuration and optional IP diagnostics.';

    public function handle(IpInfoManager $ipInfo): int
    {
        $settings = [
            'cache.enabled' => (bool) config('ip-info.cache.enabled'),
            'cache.store' => config('ip-info.cache.store') ?? 'default',
            'cache.prefix' => config('ip-info.cache.prefix'),
            'database.enabled' => (bool) config('ip-info.database.enabled'),
            'cleantalk.enabled' => (bool) config('ip-info.cleantalk.enabled'),
            'routes.enabled' => (bool) config('ip-info.routes.enabled'),
            'ip_country_table' => Schema::hasTable('ip_country') ? 'present' : 'missing',
        ];

        $ip = $this->argument('ip');
        $lookup = null;

        if (is_string($ip) && $ip !== '') {
            $lookup = $ipInfo->for($ip)->result()->toArray();
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'package' => 'suprun-bohdan/laravel-ip-info',
                'settings' => $settings,
                'lookup' => $lookup,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->line('Laravel IP Info diagnostics');
        $this->newLine();

        $this->table(['Setting', 'Value'], collect($settings)->map(
            fn (mixed $value, string $key): array => [$key, is_bool($value) ? ($value ? 'true' : 'false') : (string) $value]
        )->values()->all());

        if ($lookup !== null) {
            $this->newLine();
            $this->table(['Field', 'Value'], collect($lookup)->map(
                fn (mixed $value, string $key): array => [$key, $value === null ? 'null' : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value)]
            )->values()->all());
        }

        return self::SUCCESS;
    }
}
