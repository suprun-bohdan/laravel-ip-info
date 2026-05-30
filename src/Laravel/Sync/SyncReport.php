<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

final readonly class SyncReport
{
    /**
     * @param  list<string>  $actions
     */
    public function __construct(
        public bool $healthy,
        public SyncStatus $configStatus,
        public string $configPath,
        public bool $configCached,
        public SyncStatus $middlewarePublishedStatus,
        public string $middlewarePath,
        public string $middlewareRegistered,
        public SyncStatus $routesStatus,
        public bool $routesEnabled,
        public string $routesPath,
        /** @var list<string> */
        public array $routesMiddleware,
        public bool $databaseEnabled,
        public bool $databaseStale,
        public string $databaseTable,
        public bool $locationDbEnabled,
        public bool $locationDbStale,
        public bool $locationDbInstalled,
        public string $locationDbEdition,
        public bool $maxmindEnabled,
        public bool $maxmindStale,
        public bool $maxmindInstalled,
        public bool $trustedHeadersWithoutProxyCidrs,
        public bool $insecureHttpDriverActive,
        public bool $routesEnabledWithoutMiddleware,
        /** @var array{name: ?string, env_recommendations: list<string>, config_recommendations: array<string, mixed>} */
        public array $preset,
        /** @var list<string> */
        /** @var array<string, string> */
        public array $middlewareSnippets,
        public array $actions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'healthy' => $this->healthy,
            'config' => [
                'status' => $this->configStatus->value,
                'path' => $this->configPath,
                'cached' => $this->configCached,
            ],
            'middleware' => [
                'published' => $this->middlewarePublishedStatus->value,
                'path' => $this->middlewarePath,
                'registered' => $this->middlewareRegistered,
            ],
            'routes' => [
                'enabled' => $this->routesEnabled,
                'path' => $this->routesPath,
                'middleware' => $this->routesMiddleware,
                'status' => $this->routesStatus->value,
            ],
            'database' => [
                'enabled' => $this->databaseEnabled,
                'stale' => $this->databaseStale,
                'table' => $this->databaseTable,
            ],
            'location_db' => [
                'enabled' => $this->locationDbEnabled,
                'edition' => $this->locationDbEdition,
                'stale' => $this->locationDbStale,
                'installed' => $this->locationDbInstalled,
            ],
            'maxmind' => [
                'enabled' => $this->maxmindEnabled,
                'stale' => $this->maxmindStale,
                'installed' => $this->maxmindInstalled,
            ],
            'security' => [
                'trusted_headers_without_proxy_cidrs' => $this->trustedHeadersWithoutProxyCidrs,
                'insecure_http_driver_active' => $this->insecureHttpDriverActive,
                'routes_enabled_without_middleware' => $this->routesEnabledWithoutMiddleware,
            ],
            'preset' => $this->preset,
            'middleware_snippets' => $this->middlewareSnippets,
            'actions' => $this->actions,
        ];
    }
}
