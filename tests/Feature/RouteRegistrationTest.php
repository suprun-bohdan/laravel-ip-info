<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Route;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class RouteRegistrationTest extends TestCase
{
    public function test_it_does_not_register_route_by_default(): void
    {
        $this->assertFalse(Route::has('/ip-info'));
    }
}

final class RouteRegistrationWhenEnabledTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('ip-info.routes.enabled', true);
    }

    public function test_it_registers_route_when_enabled(): void
    {
        $this->assertTrue(Route::has('/ip-info'));
    }
}
