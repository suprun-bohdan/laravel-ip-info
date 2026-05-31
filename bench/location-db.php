<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$warmIterations = bench_iterations('BENCH_MMDB_WARM_ITERATIONS', 5000);
$coldIterations = bench_iterations('BENCH_MMDB_COLD_ITERATIONS', 500);

$fixtureDir = bench_location_db_fixture_dir();

if ($fixtureDir === null) {
    exit(0);
}

bench_app([
    'ip-info.cache.enabled' => false,
    'ip-info.lookup.request_memo' => false,
    'ip-info.location_db.enabled' => true,
    'ip-info.location_db.edition' => 'country',
    'ip-info.location_db.storage_dir' => $fixtureDir,
    'ip-info.providers.chain' => ['location_db', 'null'],
]);

$warm = bench_measure(
    'location_db MMDB warm (8.8.8.8)',
    static fn (): ?string => IpInfo::for('8.8.8.8')->countryCode(),
    $warmIterations,
    warmup: 50,
);

bench_print($warm);

$cold = bench_measure(
    'location_db MMDB cold-ish (unique 8.8.8.x)',
    function () use ($coldIterations): void {
        static $i = 1;

        IpInfo::for(bench_public_ipv4(8, 8, $i))->countryCode();
        $i++;
    },
    $coldIterations,
    warmup: 50,
);

bench_print($cold);

$country = IpInfo::for('8.8.8.8')->countryCode();
echo sprintf("Sanity check 8.8.8.8 => %s\n", $country ?? 'null');

if ($country === null) {
    fwrite(STDERR, "FAIL: expected country for 8.8.8.8 via location_db\n");
    exit(1);
}

$exitCode = bench_fail_if_above($warm, (float) (getenv('BENCH_MMDB_WARM_MAX_MS') ?: 0.5));
$exitCode |= bench_fail_if_above($cold, (float) (getenv('BENCH_MMDB_COLD_MAX_MS') ?: 2.0));

exit($exitCode);
