<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Contracts\BatchIpProvider;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Providers\NullProvider;

final class ChainProviderBatchTest extends TestCase
{
    public function test_lookup_many_uses_batch_provider(): void
    {
        $batch = new class implements BatchIpProvider, IpProvider
        {
            public int $batchCalls = 0;

            public function lookup(IpAddress $ip): ProviderResult
            {
                return ProviderResult::skipped('single');
            }

            public function lookupMany(array $addresses): array
            {
                $this->batchCalls++;
                $results = [];

                foreach ($addresses as $address) {
                    $results[$address->value] = ProviderResult::hit('US', 'batch');
                }

                return $results;
            }
        };

        $chain = new ChainProvider([$batch, new NullProvider]);
        $results = $chain->lookupMany([
            new IpAddress('8.8.8.8'),
            new IpAddress('1.1.1.1'),
        ]);

        $this->assertSame(1, $batch->batchCalls);
        $this->assertSame('US', $results['8.8.8.8']->countryCode);
        $this->assertSame('US', $results['1.1.1.1']->countryCode);
    }
}
