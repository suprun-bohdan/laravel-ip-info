<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SuprunBohdan\IpInfo\Laravel\IpInfoServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            IpInfoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('ip-info.cache.enabled', true);
        $app['config']->set('ip-info.cache.store', 'array');
        $app['config']->set('ip-info.database.enabled', false);
        $app['config']->set('ip-info.cleantalk.enabled', false);
        $app['config']->set('ip-info.routes.enabled', false);
        $app['config']->set('ip-info.trusted_proxies.headers', []);
        $app['config']->set('ip-info.trusted_proxies.respect_laravel', true);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
