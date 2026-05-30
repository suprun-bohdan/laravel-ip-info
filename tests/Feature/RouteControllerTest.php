<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class RouteControllerTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('ip-info.routes.enabled', true);
        $app['config']->set('ip-info.routes.path', '/ip-info');
        $app['config']->set('ip-info.routes.middleware', []);
    }

    public function test_ip_info_route_returns_json_payload(): void
    {
        IpInfo::fake(['127.0.0.1' => null]);

        $response = $this->get('/ip-info');

        $response->assertOk();
        $response->assertJsonStructure(['ip', 'country', 'is_public', 'is_private', 'provider']);
    }
}
