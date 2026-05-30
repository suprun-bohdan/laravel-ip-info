<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

final class MiddlewareRegistrationDetector
{
    private const MIDDLEWARE_CLASS = 'SuprunBohdan\\IpInfo\\Laravel\\Http\\Middleware\\ResolveClientIp';

    /**
     * @return 'registered'|'missing'|'unknown'
     */
    public function detect(): string
    {
        $files = $this->candidateFiles();
        $scanned = false;

        foreach ($files as $file) {
            if (! file_exists($file)) {
                continue;
            }

            $scanned = true;
            $contents = (string) file_get_contents($file);

            if ($this->containsMiddlewareReference($contents)) {
                return 'registered';
            }
        }

        return $scanned ? 'missing' : 'unknown';
    }

    /**
     * @return array<string, string>
     */
    public function middlewareSnippets(): array
    {
        return [
            'laravel_11' => <<<'PHP'
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp::class);
})
PHP,
            'laravel_10' => <<<'PHP'
// app/Http/Kernel.php — add to $middleware array:
\SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp::class,
PHP,
        ];
    }

    /**
     * @return list<string>
     */
    private function candidateFiles(): array
    {
        $files = [
            base_path('bootstrap/app.php'),
            app_path('Http/Kernel.php'),
        ];

        $routesPath = base_path('routes');

        if (is_dir($routesPath)) {
            $glob = glob($routesPath.'/*.php');

            if (is_array($glob)) {
                $files = array_merge($files, $glob);
            }
        }

        return $files;
    }

    private function containsMiddlewareReference(string $contents): bool
    {
        if (str_contains($contents, self::MIDDLEWARE_CLASS)) {
            return true;
        }

        if (str_contains($contents, 'ResolveClientIp::class')) {
            return true;
        }

        return str_contains($contents, "'ResolveClientIp'")
            || str_contains($contents, '"ResolveClientIp"');
    }
}
