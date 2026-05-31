<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use SuprunBohdan\IpInfo\Tests\TestCase;

/**
 * @param  array<string, mixed>  $configOverrides
 */
function bench_app(array $configOverrides = []): Application
{
    $case = new class('bench-bootstrap') extends TestCase
    {
        protected function setUp(): void {}

        protected function getEnvironmentSetUp($app): void
        {
            parent::getEnvironmentSetUp($app);

            foreach ($GLOBALS['bench_config_overrides'] ?? [] as $key => $value) {
                $app['config']->set($key, $value);
            }
        }
    };

    $GLOBALS['bench_config_overrides'] = $configOverrides;

    $app = $case->createApplication();
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    return $app;
}

function bench_iterations(string $envKey, int $default): int
{
    $value = getenv($envKey);

    if ($value === false || $value === '') {
        return $default;
    }

    return max(1, (int) $value);
}

/**
 * @return array{label: string, iterations: int, total_ms: float, per_op_ms: float}
 */
function bench_measure(string $label, callable $callback, int $iterations, int $warmup = 10): array
{
    for ($i = 0; $i < $warmup; $i++) {
        $callback();
    }

    $start = hrtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }

    $totalMs = (hrtime(true) - $start) / 1_000_000;

    return [
        'label' => $label,
        'iterations' => $iterations,
        'total_ms' => $totalMs,
        'per_op_ms' => $totalMs / $iterations,
    ];
}

/**
 * @param  array{label: string, iterations: int, total_ms: float, per_op_ms: float}  $result
 */
function bench_print(array $result): void
{
    echo sprintf(
        "%s: %.3f ms total, %.4f ms/op (%d iterations)\n",
        $result['label'],
        $result['total_ms'],
        $result['per_op_ms'],
        $result['iterations'],
    );
}

function bench_fail_if_above(array $result, float $maxPerOpMs, string $note = ''): int
{
    if ($result['per_op_ms'] <= $maxPerOpMs) {
        return 0;
    }

    $suffix = $note !== '' ? " ({$note})" : '';
    fwrite(
        STDERR,
        sprintf(
            "FAIL: %s exceeded %.4f ms/op threshold (actual %.4f ms/op)%s\n",
            $result['label'],
            $maxPerOpMs,
            $result['per_op_ms'],
            $suffix,
        ),
    );

    return 1;
}

function bench_public_ipv4(int $secondOctet, int $thirdOctet, int $index): string
{
    $last = ($index % 254) + 1;

    return sprintf('%d.%d.%d.%d', 8, $secondOctet, $thirdOctet, $last);
}

function bench_location_db_fixture_dir(): ?string
{
    $dir = __DIR__.'/fixtures/location-db';
    $ipv4 = $dir.'/country-ipv4.mmdb';

    if (is_readable($ipv4)) {
        return $dir;
    }

    if ((string) getenv('SKIP_LOCATION_DB_BENCH') === '1') {
        echo "SKIP: location DB benchmark (SKIP_LOCATION_DB_BENCH=1)\n";

        return null;
    }

    if (! class_exists(\MaxMind\Db\Reader::class)) {
        echo "SKIP: location DB benchmark (composer require maxmind-db/reader)\n";

        return null;
    }

    if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
        throw new RuntimeException("Unable to create fixture directory [{$dir}].");
    }

    $url = 'https://cdn.jsdelivr.net/npm/@ip-location-db/dbip-country-mmdb/dbip-country-ipv4.mmdb';

    echo "Downloading location DB fixture (country ipv4)...\n";

    $context = stream_context_create([
        'http' => [
            'timeout' => 300,
            'header' => "User-Agent: laravel-ip-info-bench\r\n",
        ],
    ]);

    $bytes = @file_get_contents($url, false, $context);

    if ($bytes === false || $bytes === '') {
        throw new RuntimeException("Failed to download MMDB from [{$url}].");
    }

    file_put_contents($ipv4, $bytes);

    echo sprintf("Fixture ready: %s (%.1f MB)\n", $ipv4, strlen($bytes) / 1_048_576);

    return $dir;
}
