<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Events;

use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;

final class IpLookupCompleted
{
    public function __construct(
        public IpAddress $address,
        public IpInfoResult $result,
    ) {}
}
