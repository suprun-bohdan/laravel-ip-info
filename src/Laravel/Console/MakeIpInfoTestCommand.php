<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class MakeIpInfoTestCommand extends Command
{
    protected $signature = 'make:ip-info-test {name : The name of the test class}';

    protected $description = 'Create a feature test stub with IpInfo::fake().';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $class = Str::studly($name);

        if (! Str::endsWith($class, 'Test')) {
            $class .= 'Test';
        }

        $directory = base_path('tests/Feature');
        $path = $directory.'/'.$class.'.php';

        if (file_exists($path)) {
            $this->error("Test already exists: {$path}");

            return self::FAILURE;
        }

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error("Unable to create directory: {$directory}");

            return self::FAILURE;
        }

        $stub = file_get_contents(__DIR__.'/../../stubs/IpInfoFeatureTest.php.stub');

        if ($stub === false) {
            $this->error('Stub file missing.');

            return self::FAILURE;
        }

        $namespace = $this->detectNamespace($directory);

        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $class],
            $stub,
        );

        file_put_contents($path, $contents);
        $this->info("Test created: {$path}");

        return self::SUCCESS;
    }

    private function detectNamespace(string $directory): string
    {
        $relative = Str::after($directory, base_path('tests').'/');

        if ($relative === $directory) {
            return 'Tests\\Feature';
        }

        $parts = array_map(
            fn (string $part): string => Str::studly($part),
            explode('/', $relative),
        );

        return 'Tests\\'.implode('\\', $parts);
    }
}
