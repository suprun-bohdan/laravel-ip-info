<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Events;

use SuprunBohdan\IpInfo\Contracts\IpProvider;

final class IpInfoBuildingChain
{
    /**
     * @param  list<IpProvider>  $providers
     */
    public function __construct(public array $providers) {}
}
