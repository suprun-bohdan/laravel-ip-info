<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use SuprunBohdan\IpInfo\Laravel\Console\MiddlewareRegistrar;
use SuprunBohdan\IpInfo\Laravel\Console\ScheduleStubPublisher;

final class IpInfoSyncFixer
{
    public function __construct(private PublishedFileComparator $fileComparator) {}

    /**
     * @return list<string>
     */
    public function apply(
        Command $command,
        SyncReport $report,
        bool $publishConfig,
        bool $publishMiddleware,
        bool $runMigrate,
        bool $force,
        bool $registerMiddleware = false,
        bool $withSchedule = false,
    ): array {
        $messages = [];

        if ($publishConfig || $this->shouldPublishConfig($report->configStatus)) {
            if ($this->publishIfAllowed($command, 'ip-info-config', $report->configStatus, $force, $messages)) {
                $messages[] = 'Published config/ip-info.php';
            }
        }

        if ($publishMiddleware || $this->shouldPublishMiddleware($report->middlewarePublishedStatus)) {
            if ($this->publishIfAllowed($command, 'ip-info-middleware', $report->middlewarePublishedStatus, $force, $messages)) {
                $messages[] = 'Published middleware stubs';
            }
        }

        if ($runMigrate) {
            Artisan::call('migrate', ['--force' => true]);
            $messages[] = trim(Artisan::output()) !== '' ? trim(Artisan::output()) : 'Ran migrations';
        }

        if ($registerMiddleware) {
            /** @var MiddlewareRegistrar $registrar */
            $registrar = app(MiddlewareRegistrar::class);

            if ($registrar->register($command, $force)) {
                $messages[] = 'Registered ResolveClientIp middleware';
            }
        }

        if ($withSchedule) {
            /** @var ScheduleStubPublisher $publisher */
            $publisher = app(ScheduleStubPublisher::class);

            if ($publisher->appendToConsole($command)) {
                $messages[] = 'Appended schedule stubs to routes/console.php';
            }
        }

        return $messages;
    }

    private function shouldPublishConfig(SyncStatus $status): bool
    {
        return $status === SyncStatus::Missing;
    }

    private function shouldPublishMiddleware(SyncStatus $status): bool
    {
        return $status === SyncStatus::Missing;
    }

    /**
     * @param  list<string>  $messages
     */
    private function publishIfAllowed(
        Command $command,
        string $tag,
        SyncStatus $status,
        bool $force,
        array &$messages,
    ): bool {
        if (! $this->fileComparator->canOverwrite($status, $force)) {
            if ($status === SyncStatus::Modified) {
                $command->warn('Skipped publishing ['.$tag.']: published file was modified locally.');
            }

            return false;
        }

        return $this->publishTag($command, $tag, $force);
    }

    private function publishTag(Command $command, string $tag, bool $force): bool
    {
        $options = ['--tag' => $tag];

        if ($force) {
            $options['--force'] = true;
        }

        $command->call('vendor:publish', $options);

        return true;
    }
}
