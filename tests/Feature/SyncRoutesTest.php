<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Route;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class SyncRoutesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('ip-info.routes.enabled', true);
        $app['config']->set('ip-info.routes.path', '/ip-info-test');
        $app['config']->set('ip-info.routes.middleware', ['web']);
    }

    public function test_routes_middleware_config_applied(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route): bool => in_array('GET', $route->methods(), true)
                && $route->uri() === 'ip-info-test'
        );

        $this->assertNotNull($route);
        $this->assertContains('web', $route->middleware());
    }
}
