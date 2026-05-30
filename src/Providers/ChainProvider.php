<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;

final class ChainProvider implements IpProvider
{
    /**
     * @param  list<IpProvider>  $providers
     */
    public function __construct(private array $providers) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $provider->lookup($ip);

                if ($result->resolved) {
                    return $result;
                }
            } catch (ProviderException) {
                continue;
            }
        }

        /** Unresolved chain aggregate; individual providers keep their own names when resolved. */
        return new ProviderResult(null, 'chain', false);
    }
}
