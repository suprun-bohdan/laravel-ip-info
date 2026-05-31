<?php

declare(strict_types=1);

$scripts = [
    'lookup.php',
    'batch.php',
    'cache-scenarios.php',
    'location-db.php',
];

$failed = 0;

foreach ($scripts as $script) {
    $path = __DIR__.'/'.$script;

    if (! is_file($path)) {
        fwrite(STDERR, "Missing benchmark script [{$path}]\n");
        exit(1);
    }

    echo PHP_EOL.'==> '.$script.PHP_EOL;

    passthru('php '.escapeshellarg($path), $exitCode);

    if ($exitCode !== 0) {
        $failed++;
    }
}

echo PHP_EOL;

if ($failed > 0) {
    fwrite(STDERR, "Benchmark suite failed ({$failed} script(s)).\n");
    exit(1);
}

echo "Benchmark suite complete.\n";
exit(0);
