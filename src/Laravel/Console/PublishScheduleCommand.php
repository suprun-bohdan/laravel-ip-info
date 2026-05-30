<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;

final class PublishScheduleCommand extends Command
{
    protected $signature = 'ip-info:publish-schedule';

    protected $description = 'Publish scheduled update stubs for offline database and MaxMind.';

    public function handle(ScheduleStubPublisher $publisher): int
    {
        return $publisher->appendToConsole($this) ? self::SUCCESS : self::FAILURE;
    }
}
