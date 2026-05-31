<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Contracts\Events\Dispatcher;
use SuprunBohdan\IpInfo\Cache\NullIpCache;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Laravel\IpInfoQuery;
use SuprunBohdan\IpInfo\Providers\MutableIpProviderResolver;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Resolvers\StringIpResolver;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpPrivacyInspector;
use SuprunBohdan\IpInfo\Support\IpThreatInspector;
use SuprunBohdan\IpInfo\Support\IpValidator;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInfoQueryTest extends TestCase
{
    public function test_it_memoizes_lookup_result(): void
    {
        $provider = new class implements IpProvider
        {
            public int $calls = 0;

            public function lookup(IpAddress $ip): ProviderResult
            {
                $this->calls++;

                return ProviderResult::hit('US', 'test');
            }
        };

        $resolver = new MutableIpProviderResolver($provider);

        $manager = new IpInfoManager(
            $this->app->make(StringIpResolver::class),
            $this->app->make(RequestIpResolver::class),
            $this->app->make(IpValidator::class),
            $this->app->make(IpNormalizer::class),
            $this->app->make(IpPrivacyInspector::class),
            $this->app->make(IpThreatInspector::class),
            new NullIpCache,
            $this->app->make(Dispatcher::class),
            $resolver,
            $this->app->make(\SuprunBohdan\IpInfo\LocationDb\AsnMmdbEnricher::class),
        );

        $query = new IpInfoQuery($manager, new IpAddress('8.8.8.8'));

        $this->assertSame('US', $query->countryCode());
        $this->assertSame('US', $query->result()->countryCode());
        $this->assertSame('US', $query->geo()->countryCode);
        $this->assertSame(1, $provider->calls);
    }
}
