<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

final class IpInfoSyncInspector
{
    public function __construct(
        private PublishedFileComparator $fileComparator,
        private HealthChecker $healthChecker,
        private MiddlewareRegistrationDetector $middlewareRegistrationDetector,
        private PresetRecommendationBuilder $presetRecommendationBuilder,
    ) {}

    public function inspect(bool $routesOnly = false): SyncReport
    {
        $configPath = config_path('ip-info.php');
        $middlewarePath = app_path('Http/Middleware/ResolveClientIp.php');

        $configStatus = $this->fileComparator->compare($configPath, PackageStubs::configStubPath());
        $middlewarePublishedStatus = $this->fileComparator->compare(
            $middlewarePath,
            PackageStubs::resolveClientIpMiddlewareStubPath(),
        );

        $routesEnabled = (bool) config('ip-info.routes.enabled', false);
        $routesPath = (string) config('ip-info.routes.path', '/ip-info');
        $routesMiddleware = $this->normalizeRouteMiddleware(config('ip-info.routes.middleware', []));
        $routesStatus = $this->resolveRoutesStatus($routesEnabled, $routesPath, $routesMiddleware);

        $databaseEnabled = (bool) config('ip-info.database.enabled', false);
        $databaseStale = $this->healthChecker->databaseIsStale();
        $databaseTable = $this->healthChecker->ipCountryTablePresent() ? 'present' : 'missing';

        $locationDbEnabled = (bool) config('ip-info.location_db.enabled', false);
        $locationDbStale = $this->healthChecker->locationDbIsStale();
        $locationDbInstalled = $this->healthChecker->locationDbIsReadable();
        $locationDbEdition = (string) config('ip-info.location_db.edition', 'country');

        $maxmindEnabled = (bool) config('ip-info.maxmind.enabled', false);
        $maxmindStale = $this->healthChecker->maxmindIsStale();
        $maxmindInstalled = $this->healthChecker->maxmindIsReadable();

        $trustedHeadersWithoutProxyCidrs = $this->hasUnsafeTrustedHeaders();
        $insecureHttpDriverActive = $this->hasInsecureHttpDriver();
        $routesEnabledWithoutMiddleware = $routesEnabled && $routesMiddleware === [];

        $preset = $this->presetRecommendationBuilder->build();
        $middlewareRegistered = $this->middlewareRegistrationDetector->detect();
        $middlewareSnippets = $this->middlewareRegistrationDetector->middlewareSnippets();

        $configCached = file_exists(base_path('bootstrap/cache/config.php'));

        $actions = $this->buildActions(
            $configStatus,
            $middlewarePublishedStatus,
            $middlewareRegistered,
            $databaseEnabled,
            $databaseStale,
            $locationDbEnabled,
            $locationDbStale,
            $maxmindEnabled,
            $maxmindStale,
            $trustedHeadersWithoutProxyCidrs,
            $routesEnabledWithoutMiddleware,
        );

        $healthy = $this->healthChecker->isDataHealthy()
            && ! $trustedHeadersWithoutProxyCidrs
            && ! $routesEnabledWithoutMiddleware
            && $routesStatus !== SyncStatus::Unsafe
            && ($middlewareRegistered !== 'missing' || $middlewarePublishedStatus === SyncStatus::Missing);

        if ($routesOnly) {
            $healthy = $routesStatus !== SyncStatus::Unsafe && ! $routesEnabledWithoutMiddleware;
        }

        return new SyncReport(
            healthy: $healthy,
            configStatus: $configStatus,
            configPath: $this->relativePath($configPath),
            configCached: $configCached,
            middlewarePublishedStatus: $middlewarePublishedStatus,
            middlewarePath: $this->relativePath($middlewarePath),
            middlewareRegistered: $middlewareRegistered,
            routesStatus: $routesStatus,
            routesEnabled: $routesEnabled,
            routesPath: $routesPath,
            routesMiddleware: $routesMiddleware,
            databaseEnabled: $databaseEnabled,
            databaseStale: $databaseStale,
            databaseTable: $databaseTable,
            locationDbEnabled: $locationDbEnabled,
            locationDbStale: $locationDbStale,
            locationDbInstalled: $locationDbInstalled,
            locationDbEdition: $locationDbEdition,
            maxmindEnabled: $maxmindEnabled,
            maxmindStale: $maxmindStale,
            maxmindInstalled: $maxmindInstalled,
            trustedHeadersWithoutProxyCidrs: $trustedHeadersWithoutProxyCidrs,
            insecureHttpDriverActive: $insecureHttpDriverActive,
            routesEnabledWithoutMiddleware: $routesEnabledWithoutMiddleware,
            preset: $preset,
            middlewareSnippets: $middlewareSnippets,
            actions: $actions,
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeRouteMiddleware(mixed $middleware): array
    {
        if (is_string($middleware)) {
            $middleware = array_filter(array_map('trim', explode(',', $middleware)));
        }

        if (! is_array($middleware)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => is_string($value) ? trim($value) : '',
            $middleware,
        )));
    }

    /**
     * @param  list<string>  $routesMiddleware
     */
    private function resolveRoutesStatus(bool $enabled, string $path, array $routesMiddleware): SyncStatus
    {
        if (! $enabled) {
            return SyncStatus::Disabled;
        }

        if ($path === '' || ! str_starts_with($path, '/') || str_contains($path, ' ')) {
            return SyncStatus::Unsafe;
        }

        if ($routesMiddleware === []) {
            return SyncStatus::Unsafe;
        }

        return SyncStatus::Ok;
    }

    private function hasUnsafeTrustedHeaders(): bool
    {
        if (! (bool) config('ip-info.trusted_proxies.require_trusted_proxy_for_headers', true)) {
            return false;
        }

        $headers = config('ip-info.trusted_proxies.headers', []);

        if (! is_array($headers) || $headers === []) {
            return false;
        }

        $cidrs = config('ip-info.trusted_proxies.proxy_cidrs', []);

        return ! is_array($cidrs) || $cidrs === [];
    }

    private function hasInsecureHttpDriver(): bool
    {
        if (! config('ip-info.http.enabled', false)) {
            return false;
        }

        if ((bool) config('ip-info.http.allow_insecure', false)) {
            return false;
        }

        $driver = (string) config('ip-info.http.driver', 'ipinfo');
        $drivers = config('ip-info.http.drivers', []);

        if (! is_array($drivers) || ! isset($drivers[$driver]) || ! is_array($drivers[$driver])) {
            return false;
        }

        $url = (string) ($drivers[$driver]['url'] ?? '');

        return str_starts_with(strtolower($url), 'http://');
    }

    /**
     * @return list<string>
     */
    private function buildActions(
        SyncStatus $configStatus,
        SyncStatus $middlewareStatus,
        string $middlewareRegistered,
        bool $databaseEnabled,
        bool $databaseStale,
        bool $locationDbEnabled,
        bool $locationDbStale,
        bool $maxmindEnabled,
        bool $maxmindStale,
        bool $trustedHeadersWithoutProxyCidrs,
        bool $routesEnabledWithoutMiddleware,
    ): array {
        $actions = [];

        if ($configStatus === SyncStatus::Missing) {
            $actions[] = 'Publish config: php artisan vendor:publish --tag=ip-info-config';
        } elseif ($configStatus === SyncStatus::Outdated) {
            $actions[] = 'Config stub outdated: php artisan ip-info:sync --publish-config --force';
        } elseif ($configStatus === SyncStatus::Modified) {
            $actions[] = 'Published config was modified locally; review vendor stub manually';
        }

        if ($middlewareStatus === SyncStatus::Missing) {
            $actions[] = 'Publish middleware: php artisan vendor:publish --tag=ip-info-middleware';
        } elseif ($middlewareStatus === SyncStatus::Outdated) {
            $actions[] = 'Middleware stub outdated: php artisan ip-info:sync --publish-middleware --force';
        } elseif ($middlewareStatus === SyncStatus::Modified) {
            $actions[] = 'Published middleware was modified locally; review vendor stub manually';
        }

        if ($middlewareRegistered === 'missing') {
            $actions[] = 'Register ResolveClientIp middleware in bootstrap/app.php or app/Http/Kernel.php';
            $actions[] = 'Auto-register middleware: php artisan ip-info:sync --register-middleware --force';
        }

        if ($databaseEnabled && $databaseStale) {
            $actions[] = 'Refresh offline database: php artisan ip-info:update-database';
        }

        if ($locationDbEnabled && $locationDbStale) {
            $actions[] = 'Refresh location DB MMDB: php artisan ip-info:update-location-db --force';
        }

        if ($maxmindEnabled && $maxmindStale) {
            $actions[] = 'Refresh MaxMind database: php artisan ip-info:update-maxmind';
        }

        if ($trustedHeadersWithoutProxyCidrs) {
            $actions[] = 'Set IP_INFO_TRUSTED_PROXY_CIDRS or disable trusted header requirement';
            $actions[] = 'Fetch Cloudflare CIDRs: php artisan ip-info:refresh-cloudflare-cidrs --write-env-snippet';
        }

        if ($this->hasNoGeoProvidersEnabled()) {
            $actions[] = 'Enable geo lookup: php artisan ip-info:install --quick or --with-location-db --preset=offline';
        }

        if ($this->scheduleStubsMissing()) {
            $actions[] = 'Append schedule stubs: php artisan ip-info:install --with-schedule';
        }

        if ($routesEnabledWithoutMiddleware) {
            $actions[] = 'Set IP_INFO_ROUTE_MIDDLEWARE=throttle:60,1 or disable IP_INFO_ROUTES_ENABLED';
        }

        return $actions;
    }

    private function relativePath(string $path): string
    {
        $base = base_path();

        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), DIRECTORY_SEPARATOR);
        }

        return $path;
    }

    private function hasNoGeoProvidersEnabled(): bool
    {
        return ! config('ip-info.database.enabled', false)
            && ! config('ip-info.location_db.enabled', false)
            && ! config('ip-info.maxmind.enabled', false)
            && ! config('ip-info.http.enabled', false)
            && ! config('ip-info.cleantalk.enabled', false);
    }

    private function scheduleStubsMissing(): bool
    {
        $target = base_path('routes/console.php');

        if (! file_exists($target)) {
            return true;
        }

        $contents = (string) file_get_contents($target);

        return ! str_contains($contents, 'ip-info:update-database');
    }
}
