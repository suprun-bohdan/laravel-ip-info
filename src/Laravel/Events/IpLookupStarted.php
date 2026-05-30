<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Events;

use SuprunBohdan\IpInfo\Data\IpAddress;

final class IpLookupStarted
{
    public function __construct(public IpAddress $address) {}
}
