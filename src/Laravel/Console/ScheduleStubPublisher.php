<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;

final class ScheduleStubPublisher
{
    private const STUB = <<<'PHP'
// Laravel IP Info — scheduled database updates
Schedule::weekly()->command('ip-info:update-database');
Schedule::monthly()->command('ip-info:update-location-db');
Schedule::weekly()->command('ip-info:update-maxmind');
Schedule::weekly()->command('ip-info:refresh-cloudflare-cidrs');

PHP;

    public function appendToConsole(Command $command): bool
    {
        $target = base_path('routes/console.php');

        if (! file_exists($target)) {
            $command->error('routes/console.php not found. Add schedule entries manually.');

            return false;
        }

        $contents = (string) file_get_contents($target);

        if (str_contains($contents, 'ip-info:update-database')) {
            $command->info('Schedule entries already present.');

            return true;
        }

        file_put_contents($target, rtrim($contents).PHP_EOL.PHP_EOL.self::STUB);
        $command->info('Schedule stubs appended to routes/console.php');

        return true;
    }
}
