<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SuprunBohdan\IpInfo\Cache\LaravelCacheIpCache;
use SuprunBohdan\IpInfo\Cache\NullIpCache;
use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Laravel\Console\DiagnoseIpCommand;
use SuprunBohdan\IpInfo\Laravel\Console\InstallDatabaseCommand;
use SuprunBohdan\IpInfo\Laravel\Console\UpdateDatabaseCommand;
use SuprunBohdan\IpInfo\Laravel\Events\IpInfoBuildingChain;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Providers\CleanTalkProvider;
use SuprunBohdan\IpInfo\Providers\DatabaseRangeProvider;
use SuprunBohdan\IpInfo\Providers\LocalProvider;
use SuprunBohdan\IpInfo\Providers\NullProvider;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Resolvers\StringIpResolver;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class IpInfoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ip-info.php', 'ip-info');

        $this->app->singleton(IpNormalizer::class);
        $this->app->singleton(IpValidator::class);
        $this->app->singleton(StringIpResolver::class);
        $this->app->singleton(RequestIpResolver::class);

        $this->app->singleton(IpCache::class, function () {
            if (! config('ip-info.cache.enabled', true)) {
                return new NullIpCache;
            }

            return new LaravelCacheIpCache(
                config('ip-info.cache.store'),
                (string) config('ip-info.cache.prefix', 'laravel_ip_info'),
                (int) config('ip-info.cache.ttl', 86400),
            );
        });

        $this->app->singleton(IpProvider::class, function ($app) {
            return $this->buildChainProvider($app);
        });

        $this->app->singleton(IpInfoManager::class, function ($app) {
            return new IpInfoManager(
                $app->make(StringIpResolver::class),
                $app->make(RequestIpResolver::class),
                $app->make(IpValidator::class),
                $app->make(IpCache::class),
                $app->make(IpProvider::class),
            );
        });

        $this->app->alias(IpInfoManager::class, 'ip-info');
        $this->app->alias(IpInfoManager::class, IpLookupContract::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/ip-info.php' => config_path('ip-info.php'),
        ], 'ip-info-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallDatabaseCommand::class,
                UpdateDatabaseCommand::class,
                DiagnoseIpCommand::class,
            ]);
        }

        if (config('ip-info.routes.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        }
    }

    private function buildChainProvider(Application $app): ChainProvider
    {
        $map = [
            'local' => LocalProvider::class,
            'database' => DatabaseRangeProvider::class,
            'cleantalk' => CleanTalkProvider::class,
            'null' => NullProvider::class,
        ];

        $chain = config('ip-info.providers.chain', ['local', 'database', 'cleantalk']);
        $providers = [];

        foreach ($chain as $name) {
            if (! isset($map[$name])) {
                continue;
            }

            $providers[] = $app->make($map[$name]);
        }

        foreach (config('ip-info.providers.custom', []) as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $provider = $app->make($class);

            if ($provider instanceof IpProvider) {
                $providers[] = $provider;
            }
        }

        $event = new IpInfoBuildingChain($providers);
        Event::dispatch($event);
        $providers = $event->providers;

        if ($providers === []) {
            $providers[] = $app->make(NullProvider::class);
        }

        return new ChainProvider($providers);
    }
}
