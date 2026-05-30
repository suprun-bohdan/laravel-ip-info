<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Telescope;

use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupCompleted;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupFailed;

final class IpInfoTelescopeRecorder
{
    public function __construct(Dispatcher $events)
    {
        if (! class_exists(Telescope::class) || ! config('ip-info.telescope.enabled', true)) {
            return;
        }

        $events->listen(IpLookupCompleted::class, function (IpLookupCompleted $event): void {
            if (! Telescope::isRecording()) {
                return;
            }

            Telescope::recordLog(IncomingEntry::make([
                'level' => 'info',
                'message' => 'IP lookup completed',
                'context' => $event->result->forLogging()->toMinimalArray() + [
                    'provider' => $event->result->provider,
                ],
            ]));
        });

        $events->listen(IpLookupFailed::class, function (IpLookupFailed $event): void {
            if (! Telescope::isRecording()) {
                return;
            }

            Telescope::recordLog(IncomingEntry::make([
                'level' => 'error',
                'message' => 'IP lookup failed: '.$event->exception->getMessage(),
                'context' => ['ip' => $event->address->value],
            ]));
        });
    }
}
