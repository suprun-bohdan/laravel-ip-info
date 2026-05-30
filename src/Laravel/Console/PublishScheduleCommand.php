<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;

final class PublishScheduleCommand extends Command
{
    protected $signature = 'ip-info:publish-schedule';

    protected $description = 'Publish scheduled update stubs for offline database and MaxMind.';

    public function handle(): int
    {
        $target = base_path('routes/console.php');
        $stub = <<<'PHP'
// Laravel IP Info — scheduled database updates
Schedule::weekly()->command('ip-info:update-database');
Schedule::weekly()->command('ip-info:update-maxmind');

PHP;

        if (! file_exists($target)) {
            $this->error('routes/console.php not found. Add schedule entries manually.');

            return self::FAILURE;
        }

        $contents = file_get_contents($target);

        if ($contents !== false && str_contains($contents, 'ip-info:update-database')) {
            $this->info('Schedule entries already present.');

            return self::SUCCESS;
        }

        file_put_contents($target, rtrim((string) $contents).PHP_EOL.PHP_EOL.$stub);
        $this->info('Schedule stubs appended to routes/console.php');

        return self::SUCCESS;
    }
}
