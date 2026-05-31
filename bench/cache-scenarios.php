<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use Illuminate\Support\Facades\Cache;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$hitIterations = bench_iterations('BENCH_CACHE_HIT_ITERATIONS', 5000);
$missIterations = bench_iterations('BENCH_CACHE_MISS_ITERATIONS', 500);

$app = bench_app([
    'ip-info.cache.enabled' => true,
    'ip-info.cache.store' => 'array',
    'ip-info.lookup.request_memo' => false,
]);

Cache::store('array')->flush();

$fakeMap = ['8.8.8.8' => 'US'];

for ($i = 1; $i <= $missIterations; $i++) {
    $fakeMap[bench_public_ipv4(8, 4, $i)] = 'US';
}

IpInfo::fake($fakeMap);

// Prime positive cache for hit scenario.
IpInfo::for('8.8.8.8')->countryCode();

$hit = bench_measure(
    'cache hit (same public IP)',
    static fn (): ?string => IpInfo::for('8.8.8.8')->countryCode(),
    $hitIterations,
);

bench_print($hit);

$miss = bench_measure(
    'cache miss storm (unique IPs)',
    function () use ($missIterations): void {
        static $i = 1;

        IpInfo::for(bench_public_ipv4(8, 4, $i))->countryCode();
        $i++;
    },
    $missIterations,
);

bench_print($miss);

Cache::store('array')->flush();
config(['ip-info.cache.enabled' => false]);

$noCache = bench_measure(
    'no cache (unique IPs, fake provider)',
    function () use ($missIterations): void {
        static $i = 1;

        IpInfo::for(bench_public_ipv4(8, 4, $i))->countryCode();
        $i++;
    },
    $missIterations,
);

bench_print($noCache);

$negativeDir = sys_get_temp_dir().'/ip-info-bench-negative-'.getmypid();

if (! is_dir($negativeDir)) {
    mkdir($negativeDir, 0755, true);
}

bench_app([
    'ip-info.cache.enabled' => true,
    'ip-info.cache.store' => 'array',
    'ip-info.lookup.request_memo' => false,
    'ip-info.location_db.enabled' => true,
    'ip-info.location_db.storage_dir' => $negativeDir,
    'ip-info.providers.chain' => ['location_db', 'null'],
]);

Cache::store('array')->flush();

IpInfo::for('8.8.8.8')->countryCode();

$negative = bench_measure(
    'negative cache (repeated miss IP)',
    static fn (): ?string => IpInfo::for('8.8.8.8')->countryCode(),
    $hitIterations,
);

bench_print($negative);

@rmdir($negativeDir);

$exitCode = bench_fail_if_above($hit, (float) (getenv('BENCH_CACHE_HIT_MAX_MS') ?: 0.05));
$exitCode |= bench_fail_if_above($negative, (float) (getenv('BENCH_NEGATIVE_CACHE_MAX_MS') ?: 0.1));

echo sprintf(
    "Summary: miss/no-cache ratio %.2fx (lower cache overhead is better on hit path)\n",
    $noCache['per_op_ms'] > 0 ? $miss['per_op_ms'] / $noCache['per_op_ms'] : 0,
);

exit($exitCode);
