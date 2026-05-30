#!/usr/bin/env bash
set -euo pipefail

echo "==> Running package PHPUnit suite"
cd /package
composer install --no-interaction --prefer-dist
vendor/bin/phpunit

echo "==> Bootstrapping ephemeral Laravel app"
if [ ! -f /app/artisan ]; then
    composer create-project laravel/laravel /app "^11.0" --prefer-dist --no-interaction
fi

cd /app

php <<'PHP'
<?php
$appComposer = '/app/composer.json';
$data = json_decode(file_get_contents($appComposer), true);
$data['repositories'] ??= [];
$hasPath = false;
foreach ($data['repositories'] as $repo) {
    if (($repo['url'] ?? '') === '/package') {
        $hasPath = true;
        break;
    }
}
if (! $hasPath) {
    $data['repositories'][] = [
        'type' => 'path',
        'url' => '/package',
        'options' => ['symlink' => true],
    ];
}
file_put_contents($appComposer, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
PHP

composer require suprun-bohdan/laravel-ip-info:@dev --no-interaction
php artisan ip-info:install --force --register-middleware

cat > routes/ip-info-docker.php <<'ROUTES'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/geo', function () {
    return response()->json([
        'country' => client_country(default: 'XX'),
        'risk' => client_ip_risk()->toArray(),
        'tor' => ip_info()->isTor(),
    ]);
});

Route::middleware(['ip.resolve', 'ip.filter'])->get('/filtered', function () {
    return 'ok';
});
ROUTES

grep -q "ip-info-docker.php" routes/web.php || echo "require __DIR__.'/ip-info-docker.php';" >> routes/web.php

mkdir -p tests/Feature

cat > tests/Feature/IpInfoDockerTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use Tests\TestCase;

final class IpInfoDockerTest extends TestCase
{
    public function test_geo_endpoint_returns_risk_payload(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('/geo');

        $response->assertOk();
        $response->assertJsonPath('country', 'UA');
        $response->assertJsonStructure([
            'country',
            'risk' => ['score', 'level', 'signals'],
            'tor',
        ]);
    }

    public function test_filtered_route_blocks_tor_with_custom_status(): void
    {
        Config::set('ip-info.filtering.enabled', true);
        Config::set('ip-info.filtering.block_tor', true);
        Config::set('ip-info.threat_intel.tor_exit_cidrs', ['198.96.155.0/24']);
        Config::set('ip-info.filtering.responses.tor', [
            'status' => 451,
            'message' => 'Tor blocked',
        ]);

        IpInfo::fake(['198.96.155.10' => 'IS']);

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.96.155.10'])
            ->get('/filtered');

        $response->assertStatus(451);
    }
}
PHP

echo "==> Running ephemeral Laravel integration tests"
php artisan test --filter=IpInfoDockerTest

echo "==> Docker verification complete"
