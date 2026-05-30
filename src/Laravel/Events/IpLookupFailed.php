<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Events;

use SuprunBohdan\IpInfo\Data\IpAddress;
use Throwable;

final class IpLookupFailed
{
    public function __construct(
        public IpAddress $address,
        public Throwable $exception,
    ) {}
}
