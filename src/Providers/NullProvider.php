<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;

final class NullProvider implements IpProvider
{
    public function lookup(IpAddress $ip): ProviderResult
    {
        return new ProviderResult(null, 'null', false);
    }
}
