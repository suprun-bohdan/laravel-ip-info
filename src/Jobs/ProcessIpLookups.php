<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupsBatchCompleted;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;

final class ProcessIpLookups implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $ips
     */
    public function __construct(private array $ips) {}

    public function handle(IpInfoManager $ipInfo): void
    {
        $results = $ipInfo->forMany($this->ips);

        event(new IpLookupsBatchCompleted($this->ips, $results));
    }
}
