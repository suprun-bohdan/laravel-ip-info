<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\BatchIpProvider;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;

final class ChainProvider implements BatchIpProvider, IpProvider
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

                if ($result->shouldStopChain()) {
                    return $result;
                }
            } catch (ProviderException) {
                continue;
            }
        }

        return ProviderResult::skipped('chain', 'No provider resolved the address.');
    }

    /**
     * @param  list<IpAddress>  $addresses
     * @return array<string, ProviderResult>
     */
    public function lookupMany(array $addresses): array
    {
        /** @var array<string, ProviderResult> $results */
        $results = [];
        /** @var array<string, IpAddress> $remaining */
        $remaining = [];

        foreach ($addresses as $address) {
            $remaining[$address->value] = $address;
        }

        foreach ($this->providers as $provider) {
            if ($remaining === []) {
                break;
            }

            if ($provider instanceof BatchIpProvider) {
                $batchResults = $provider->lookupMany(array_values($remaining));

                foreach ($remaining as $value => $address) {
                    $result = $batchResults[$value] ?? ProviderResult::skipped($provider::class);

                    if ($result->shouldStopChain()) {
                        $results[$value] = $result;
                        unset($remaining[$value]);
                    }
                }

                continue;
            }

            foreach ($remaining as $value => $address) {
                try {
                    $result = $provider->lookup($address);

                    if ($result->shouldStopChain()) {
                        $results[$value] = $result;
                        unset($remaining[$value]);
                    }
                } catch (ProviderException) {
                    continue;
                }
            }
        }

        foreach ($remaining as $value => $address) {
            $results[$value] = ProviderResult::skipped('chain', 'No provider resolved the address.');
        }

        return $results;
    }
}
