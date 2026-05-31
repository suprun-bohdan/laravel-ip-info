#!/usr/bin/env bash
set -euo pipefail

echo "==> Package micro-benchmarks"
cd /package
composer install --no-interaction --prefer-dist
php bench/run.php || exit 1

echo "==> Bootstrapping ephemeral Laravel app for HTTP stress"
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

composer require suprun-bohdan/laravel-ip-info:@dev maxmind-db/reader --no-interaction

php artisan ip-info:install --force --register-middleware

grep -q '^IP_INFO_PRESET=offline' .env 2>/dev/null || echo 'IP_INFO_PRESET=offline' >> .env
grep -q '^IP_INFO_LOCATION_DB_ENABLED=true' .env 2>/dev/null || echo 'IP_INFO_LOCATION_DB_ENABLED=true' >> .env
grep -q '^IP_INFO_CACHE_ENABLED=true' .env 2>/dev/null || echo 'IP_INFO_CACHE_ENABLED=true' >> .env

php artisan ip-info:update-location-db --edition=country --force

cat > routes/bench-stress.php <<'ROUTES'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/bench/geo', function () {
    $ip = (string) request()->query('ip', '8.8.8.8');

    return response()->json([
        'ip' => $ip,
        'country' => ip_info($ip)->countryCode(),
        'provider' => ip_info($ip)->result()->provider,
    ]);
});
ROUTES

grep -q "bench-stress.php" routes/web.php || echo "require __DIR__.'/bench-stress.php';" >> routes/web.php

echo "==> HTTP stress (wrk) — cache hit (same IP)"
php artisan serve --host=127.0.0.1 --port=8000 >/tmp/laravel-serve.log 2>&1 &
SERVER_PID=$!

cleanup() {
    kill "${SERVER_PID}" 2>/dev/null || true
}
trap cleanup EXIT

for _ in $(seq 1 30); do
    if curl -sf "http://127.0.0.1:8000/bench/geo?ip=8.8.8.8" >/dev/null; then
        break
    fi
    sleep 0.2
done

curl -sf "http://127.0.0.1:8000/bench/geo?ip=8.8.8.8" | head -c 200
echo ""

wrk -t2 -c20 -d10s "http://127.0.0.1:8000/bench/geo?ip=8.8.8.8"

echo "==> HTTP stress (wrk) — cache miss (unique 8.8.8.x via Lua)"
wrk -t2 -c20 -d10s -s /package/bench/wrk-miss.lua "http://127.0.0.1:8000/bench/geo"

echo "==> Docker stress complete"
