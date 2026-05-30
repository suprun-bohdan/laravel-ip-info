<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

$case = new class('bench-batch') extends TestCase
{
    protected function setUp(): void {}

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('ip-info.cache.enabled', false);
    }
};

$app = $case->createApplication();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

IpInfo::fake(array_fill_keys(array_map(fn ($i) => "203.0.113.{$i}", range(1, 100)), 'US'));

$ips = array_map(fn ($i) => "203.0.113.{$i}", range(1, 100));

$start = hrtime(true);
IpInfo::forMany($ips);
$batchMs = (hrtime(true) - $start) / 1_000_000;

$start = hrtime(true);
foreach ($ips as $ip) {
    IpInfo::for($ip)->countryCode();
}
$sequentialMs = (hrtime(true) - $start) / 1_000_000;

echo sprintf("Batch forMany: %.3f ms\nSequential: %.3f ms\n", $batchMs, $sequentialMs);

exit(0);
