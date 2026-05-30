<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;

final class PublishPestCommand extends Command
{
    protected $signature = 'ip-info:publish-pest';

    protected $description = 'Publish Pest.php stub with InteractsWithIpInfo auto-use.';

    public function handle(): int
    {
        $target = base_path('tests/Pest.php');
        $stubPath = __DIR__.'/../../stubs/Pest.php.stub';
        $stub = file_get_contents($stubPath);

        if ($stub === false) {
            $this->error('Stub file missing.');

            return self::FAILURE;
        }

        if (file_exists($target)) {
            $contents = file_get_contents($target);

            if ($contents !== false && str_contains($contents, 'InteractsWithIpInfo')) {
                $this->info('InteractsWithIpInfo already registered in tests/Pest.php');

                return self::SUCCESS;
            }

            file_put_contents($target, rtrim($contents).PHP_EOL.PHP_EOL.trim($stub).PHP_EOL);
            $this->info('Appended InteractsWithIpInfo to tests/Pest.php');

            return self::SUCCESS;
        }

        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            $this->error('Unable to create tests directory.');

            return self::FAILURE;
        }

        file_put_contents($target, $stub);
        $this->info('Published tests/Pest.php');

        return self::SUCCESS;
    }
}
