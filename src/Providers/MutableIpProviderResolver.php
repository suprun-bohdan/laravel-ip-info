<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Contracts\IpProviderResolver;

final class MutableIpProviderResolver implements IpProviderResolver
{
    public function __construct(private IpProvider $provider) {}

    public function provider(): IpProvider
    {
        return $this->provider;
    }

    public function replace(IpProvider $provider): void
    {
        $this->provider = $provider;
    }
}
