<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Events;

use SuprunBohdan\IpInfo\Data\IpInfoResult;

final class IpLookupsBatchCompleted
{
    /**
     * @param  list<string>  $ips
     * @param  array<string, IpInfoResult>  $results
     */
    public function __construct(
        public array $ips,
        public array $results,
    ) {}
}
