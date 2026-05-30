<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

interface IpProviderResolver
{
    public function provider(): IpProvider;

    public function replace(IpProvider $provider): void;
}
