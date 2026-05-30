<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Contracts\IpProviderResolver;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class NegativeCacheTest extends TestCase
{
    public function test_it_caches_negative_lookup_results_without_fake(): void
    {
        config(['ip-info.lookup.request_memo' => false]);

        $provider = new class implements IpProvider
        {
            public int $calls = 0;

            public function lookup(IpAddress $ip): ProviderResult
            {
                $this->calls++;

                return ProviderResult::miss('test');
            }
        };

        $this->app->instance(IpProvider::class, new ChainProvider([$provider]));
        $this->app->make(IpProviderResolver::class)
            ->replace(new ChainProvider([$provider]));

        $first = IpInfo::for('8.8.8.8')->result();
        $second = IpInfo::for('8.8.8.8')->result();

        $this->assertNull($first->countryCode());
        $this->assertSame('test', $first->provider);
        $this->assertNull($second->countryCode());
        $this->assertSame('cache:negative', $second->provider);
        $this->assertSame(1, $provider->calls);
    }
}
