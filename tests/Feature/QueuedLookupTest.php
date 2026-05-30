<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use SuprunBohdan\IpInfo\Jobs\ProcessIpLookups;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupsBatchCompleted;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class QueuedLookupTest extends TestCase
{
    public function test_for_many_queued_dispatches_job(): void
    {
        Queue::fake();

        IpInfo::forManyQueued(['8.8.8.8', '1.1.1.1']);

        Queue::assertPushed(ProcessIpLookups::class, function (ProcessIpLookups $job): bool {
            return true;
        });
    }

    public function test_process_ip_lookups_job_dispatches_batch_event(): void
    {
        Event::fake([IpLookupsBatchCompleted::class]);

        IpInfo::fake([
            '8.8.8.8' => 'US',
            '1.1.1.1' => 'AU',
        ]);

        $job = new ProcessIpLookups(['8.8.8.8', '1.1.1.1']);
        $job->handle($this->app->make(IpInfoManager::class));

        Event::assertDispatched(IpLookupsBatchCompleted::class, function (IpLookupsBatchCompleted $event): bool {
            return count($event->ips) === 2
                && $event->results['8.8.8.8']->countryCode() === 'US';
        });
    }
}
