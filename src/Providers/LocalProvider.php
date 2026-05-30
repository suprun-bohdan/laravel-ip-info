<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class LocalProvider implements IpProvider
{
    public function __construct(private IpValidator $validator) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if ($this->validator->shouldSkipExternalLookup($ip->value)) {
            return new ProviderResult(null, 'local', true);
        }

        return new ProviderResult(null, 'local', false);
    }
}
