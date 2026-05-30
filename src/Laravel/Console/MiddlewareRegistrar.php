<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use SuprunBohdan\IpInfo\Laravel\Sync\MiddlewareRegistrationDetector;

final class MiddlewareRegistrar
{
    private const MARKER = '@ip-info-resolve-client-ip';

    private const MIDDLEWARE_CLASS = 'SuprunBohdan\\IpInfo\\Laravel\\Http\\Middleware\\ResolveClientIp';

    public function __construct(private MiddlewareRegistrationDetector $detector) {}

    public function register(Command $command, bool $force = false): bool
    {
        if ($this->detector->detect() === 'registered') {
            $command->info('ResolveClientIp middleware is already registered.');

            return true;
        }

        $bootstrap = base_path('bootstrap/app.php');

        if (file_exists($bootstrap) && $this->registerInBootstrapApp($command, $bootstrap, $force)) {
            return true;
        }

        $kernel = app_path('Http/Kernel.php');

        if (file_exists($kernel)) {
            return $this->registerInKernel($command, $kernel, $force);
        }

        $command->warn('Could not find bootstrap/app.php or app/Http/Kernel.php.');

        return false;
    }

    private function registerInBootstrapApp(Command $command, string $path, bool $force): bool
    {
        $contents = (string) file_get_contents($path);

        if (str_contains($contents, self::MARKER)) {
            $command->info('ResolveClientIp marker already present in bootstrap/app.php.');

            return true;
        }

        if (! str_contains($contents, '->withMiddleware(')) {
            return false;
        }

        $snippet = <<<'PHP'

        // @ip-info-resolve-client-ip
        $middleware->append(\SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp::class);
PHP;

        $updated = preg_replace(
            '/->withMiddleware\s*\(\s*function\s*\(\s*[^)]+\)\s*\{/',
            '$0'.$snippet,
            $contents,
            1,
        );

        if (! is_string($updated) || $updated === $contents) {
            $command->warn('Could not inject middleware into bootstrap/app.php automatically.');

            return false;
        }

        if (! $force && ! $command->confirm('Modify bootstrap/app.php to register ResolveClientIp middleware?', true)) {
            return false;
        }

        file_put_contents($path, $updated);
        $command->info('Registered ResolveClientIp in bootstrap/app.php');

        return true;
    }

    private function registerInKernel(Command $command, string $path, bool $force): bool
    {
        $contents = (string) file_get_contents($path);

        if (str_contains($contents, self::MARKER) || str_contains($contents, self::MIDDLEWARE_CLASS)) {
            $command->info('ResolveClientIp already referenced in app/Http/Kernel.php.');

            return true;
        }

        $line = "            // @ip-info-resolve-client-ip\n            \\SuprunBohdan\\IpInfo\\Laravel\\Http\\Middleware\\ResolveClientIp::class,";

        $updated = preg_replace(
            '/protected\s+\$middleware\s*=\s*\[/',
            "protected \$middleware = [\n".$line,
            $contents,
            1,
        );

        if (! is_string($updated) || $updated === $contents) {
            $command->warn('Could not inject middleware into app/Http/Kernel.php automatically.');

            return false;
        }

        if (! $force && ! $command->confirm('Modify app/Http/Kernel.php to register ResolveClientIp middleware?', true)) {
            return false;
        }

        file_put_contents($path, $updated);
        $command->info('Registered ResolveClientIp in app/Http/Kernel.php');

        return true;
    }
}
