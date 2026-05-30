<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;

interface BatchIpProvider
{
    /**
     * @param  list<IpAddress>  $addresses
     * @return array<string, ProviderResult>
     */
    public function lookupMany(array $addresses): array;
}
