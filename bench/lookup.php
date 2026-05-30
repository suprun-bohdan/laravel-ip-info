<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

$case = new class('bench') extends TestCase
{
    protected function setUp(): void {}
};

$app = $case->createApplication();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

IpInfo::fake(['8.8.8.8' => 'US']);

$iterations = 1000;
$start = hrtime(true);

for ($i = 0; $i < $iterations; $i++) {
    IpInfo::for('8.8.8.8')->countryCode();
}

$elapsedMs = (hrtime(true) - $start) / 1_000_000;
$perLookup = $elapsedMs / $iterations;

echo sprintf("Cached fake lookups: %.3f ms total, %.4f ms/lookup\n", $elapsedMs, $perLookup);

if ($perLookup > 1.0) {
    fwrite(STDERR, "WARN: cached lookup exceeded 1ms target\n");
    exit(1);
}

exit(0);
