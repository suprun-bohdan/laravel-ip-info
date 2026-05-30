<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Contracts\Ingest;
use Laravel\Pulse\Facades\Pulse;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuprunBohdan\IpInfo\Cache\LaravelCacheIpCache;
use SuprunBohdan\IpInfo\Cache\NullIpCache;
use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Contracts\IpHttpClient;
use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Contracts\SchemaInspector;
use SuprunBohdan\IpInfo\Http\Psr18IpHttpClient;
use SuprunBohdan\IpInfo\Laravel\Console\DiagnoseIpCommand;
use SuprunBohdan\IpInfo\Laravel\Console\InstallCommand;
use SuprunBohdan\IpInfo\Laravel\Console\InstallDatabaseCommand;
use SuprunBohdan\IpInfo\Laravel\Console\StarterKitCommand;
use SuprunBohdan\IpInfo\Laravel\Console\UpdateDatabaseCommand;
use SuprunBohdan\IpInfo\Laravel\Console\UpdateMaxMindCommand;
use SuprunBohdan\IpInfo\Laravel\Database\LaravelSchemaInspector;
use SuprunBohdan\IpInfo\Laravel\Events\IpInfoBuildingChain;
use SuprunBohdan\IpInfo\Laravel\Http\LaravelIpHttpClient;
use SuprunBohdan\IpInfo\Laravel\Pulse\Recorders\IpInfoRecorder;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Providers\CleanTalkProvider;
use SuprunBohdan\IpInfo\Providers\DatabaseRangeProvider;
use SuprunBohdan\IpInfo\Providers\HttpIpProvider;
use SuprunBohdan\IpInfo\Providers\LocalProvider;
use SuprunBohdan\IpInfo\Providers\MaxMindProvider;
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
        $this->app->singleton(SchemaInspector::class, LaravelSchemaInspector::class);

        $this->app->singleton(IpHttpClient::class, function ($app) {
            if ($app->bound(ClientInterface::class) && $app->bound(RequestFactoryInterface::class)) {
                return new Psr18IpHttpClient(
                    $app->make(ClientInterface::class),
                    $app->make(RequestFactoryInterface::class),
                );
            }

            return new LaravelIpHttpClient($app->make(HttpFactory::class));
        });

        $this->app->singleton(IpCache::class, function ($app) {
            if (! config('ip-info.cache.enabled', true)) {
                return new NullIpCache;
            }

            $store = config('ip-info.cache.store');
            $cache = $app->make('cache');
            $repository = is_string($store) && $store !== ''
                ? $cache->store($store)
                : $cache->store();

            return new LaravelCacheIpCache(
                $repository,
                (string) config('ip-info.cache.prefix', 'laravel_ip_info'),
                (int) config('ip-info.cache.ttl', 86400),
                (int) config('ip-info.cache.negative_ttl', 300),
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
                $app->make(Dispatcher::class),
            );
        });

        $this->app->alias(IpInfoManager::class, 'ip-info');
        $this->app->alias(IpInfoManager::class, IpLookupContract::class);

        $this->registerPulseRecorder();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/ip-info.php' => config_path('ip-info.php'),
        ], 'ip-info-config');

        $this->publishes([
            __DIR__.'/Http/Middleware/ResolveClientIp.php' => app_path('Http/Middleware/ResolveClientIp.php'),
        ], 'ip-info-middleware');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                InstallDatabaseCommand::class,
                UpdateDatabaseCommand::class,
                UpdateMaxMindCommand::class,
                DiagnoseIpCommand::class,
                StarterKitCommand::class,
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
            'maxmind' => MaxMindProvider::class,
            'http' => HttpIpProvider::class,
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
        $app->make(Dispatcher::class)->dispatch($event);
        $providers = $event->providers;

        if ($providers === []) {
            $providers[] = $app->make(NullProvider::class);
        }

        return new ChainProvider($providers);
    }

    private function registerPulseRecorder(): void
    {
        if (! interface_exists(Ingest::class)) {
            return;
        }

        $this->app->singleton(IpInfoRecorder::class);

        $this->app->booted(function (): void {
            if (! class_exists(Pulse::class)) {
                return;
            }

            Pulse::register([
                IpInfoRecorder::class,
            ]);
        });
    }
}
