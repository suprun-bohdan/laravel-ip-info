<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use SuprunBohdan\IpInfo\Http\HttpCircuitBreaker;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Laravel\Sync\HealthChecker;

final class DiagnoseIpCommand extends Command
{
    protected $signature = 'ip-info:diagnose
                            {ip? : IP address to inspect}
                            {--json : Output diagnostics as JSON}';

    protected $description = 'Show package configuration and optional IP diagnostics.';

    public function __construct(
        private HealthChecker $healthChecker,
        private HttpCircuitBreaker $circuitBreaker,
    ) {
        parent::__construct();
    }

    public function handle(IpInfoManager $ipInfo): int
    {
        $databaseStale = $this->healthChecker->databaseIsStale();
        $locationDbStale = $this->healthChecker->locationDbIsStale();
        $locationDbAsnStale = $this->healthChecker->asnDbIsStale();
        $maxmindStale = $this->healthChecker->maxmindIsStale();
        $maxmindReadable = $this->healthChecker->maxmindIsReadable();
        $driver = (string) config('ip-info.http.driver', 'ip-api');

        $settings = [
            'cache.enabled' => (bool) config('ip-info.cache.enabled'),
            'cache.store' => config('ip-info.cache.store') ?? 'default',
            'cache.prefix' => config('ip-info.cache.prefix'),
            'cache.negative_ttl' => (int) config('ip-info.cache.negative_ttl', 300),
            'database.enabled' => (bool) config('ip-info.database.enabled'),
            'database.stale' => $databaseStale,
            'location_db.enabled' => (bool) config('ip-info.location_db.enabled'),
            'location_db.edition' => (string) config('ip-info.location_db.edition', 'country'),
            'location_db.stale' => $locationDbStale,
            'location_db.readable' => $this->healthChecker->locationDbIsReadable(),
            'location_db.enrich_asn' => (bool) config('ip-info.location_db.enrich_asn', false),
            'location_db.asn_stale' => $locationDbAsnStale,
            'location_db.asn_installed' => $this->healthChecker->asnDbIsInstalled(),
            'maxmind.enabled' => (bool) config('ip-info.maxmind.enabled'),
            'maxmind.edition' => (string) config('ip-info.maxmind.edition', 'country'),
            'maxmind.stale' => $maxmindStale,
            'maxmind.readable' => $maxmindReadable,
            'http.enabled' => (bool) config('ip-info.http.enabled'),
            'http.circuit_open' => $this->circuitBreaker->isOpen('http:'.$driver),
            'cleantalk.enabled' => (bool) config('ip-info.cleantalk.enabled'),
            'cleantalk.circuit_open' => $this->circuitBreaker->isOpen('cleantalk'),
            'routes.enabled' => (bool) config('ip-info.routes.enabled'),
            'privacy.redact_headers' => (bool) config('ip-info.privacy.redact_headers', false),
            'trusted_proxies.headers' => $this->formatTrustedHeaders(),
            'ip_country_table' => $this->healthChecker->ipCountryTablePresent() ? 'present' : 'missing',
        ];

        $ip = $this->argument('ip');
        $lookup = null;

        if (is_string($ip) && $ip !== '') {
            $lookup = $ipInfo->for($ip)->result()->toArray();
        }

        $healthy = $this->healthChecker->isDataHealthy();

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

        if ($locationDbStale) {
            $this->warn('Location DB MMDB is missing or stale. Run ip-info:update-location-db --force.');
        }

        if ($locationDbAsnStale) {
            $this->warn('ASN MMDB is missing or stale. Run ip-info:update-location-db --edition=asn --force.');
        }

        if ($maxmindStale) {
            $edition = (string) config('ip-info.maxmind.edition', 'country');
            $this->warn("MaxMind database is missing or stale. Run ip-info:update-maxmind --edition={$edition}.");
        }

        $this->newLine();
        $this->line('Application integration: php artisan ip-info:sync --json');

        if ($lookup !== null) {
            $this->newLine();
            $lookupRows = [];

            foreach ($lookup as $key => $value) {
                $lookupRows[] = [
                    (string) $key,
                    $value === null ? 'null' : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value),
                ];
            }

            $this->table(['Field', 'Value'], $lookupRows);
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<string>
     */
    private function formatTrustedHeaders(): array
    {
        $headers = config('ip-info.trusted_proxies.headers', []);

        if (! is_array($headers)) {
            return [];
        }

        if (! config('ip-info.privacy.redact_headers', false)) {
            return array_values(array_map('strval', $headers));
        }

        return array_map(
            fn (mixed $header): string => is_string($header) ? '[redacted:'.$header.']' : '[redacted]',
            $headers,
        );
    }
}
