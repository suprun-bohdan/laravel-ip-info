<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Contracts\BatchIpProvider;
use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Contracts\IpProviderResolver;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Jobs\ProcessIpLookups;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupCompleted;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupFailed;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupStarted;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Resolvers\StringIpResolver;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpPrivacyInspector;
use SuprunBohdan\IpInfo\Support\IpThreatInspector;
use SuprunBohdan\IpInfo\Support\IpValidator;
use SuprunBohdan\IpInfo\Testing\FakeIpProvider;
use Throwable;

final class IpInfoManager implements IpLookupContract
{
    /** @var array<string, IpInfoResult> */
    private array $requestMemo = [];

    private ?FakeIpProvider $lastFake = null;

    private bool $fakeMode = false;

    public function __construct(
        private StringIpResolver $stringResolver,
        private RequestIpResolver $requestResolver,
        private IpValidator $validator,
        private IpNormalizer $normalizer,
        private IpPrivacyInspector $privacyInspector,
        private IpThreatInspector $threatInspector,
        private IpCache $cache,
        private Dispatcher $events,
        private IpProviderResolver $providerResolver,
    ) {}

    /**
     * @param  array<string, string|null>  $map
     */
    public function fake(array $map = []): FakeIpProvider
    {
        $fake = new FakeIpProvider($map);
        $this->enableFakeProvider($fake);

        return $fake;
    }

    /**
     * @param  list<string|null>  $sequence
     */
    public function fakeSequence(array $sequence): FakeIpProvider
    {
        $fake = new FakeIpProvider([], $sequence);
        $this->enableFakeProvider($fake);

        return $fake;
    }

    public function assertLookedUp(string $ip): void
    {
        if ($this->lastFake === null) {
            throw new \RuntimeException('Call IpInfo::fake() or fakeSequence() before assertLookedUp().');
        }

        $this->lastFake->assertLookedUp($ip);
    }

    public function withCachePrefix(string $prefix): self
    {
        app()->instance('ip-info.runtime_cache_prefix', $prefix);

        return $this;
    }

    public function for(string $ip): IpInfoQuery
    {
        return new IpInfoQuery($this, $this->stringResolver->resolve($ip));
    }

    public function forRequest(Request $request): IpInfoQuery
    {
        return new IpInfoQuery($this, $this->requestResolver->resolve($request));
    }

    /**
     * @param  list<string>  $ips
     * @return array<string, IpInfoResult>
     */
    public function forMany(array $ips): array
    {
        $results = [];
        $misses = [];

        foreach ($ips as $ip) {
            $address = $this->stringResolver->resolve($ip);

            if ($this->shouldUseRequestMemo() && isset($this->requestMemo[$address->value])) {
                $results[$ip] = $this->requestMemo[$address->value];

                continue;
            }

            $cached = $this->resolveFromCache($address);

            if ($cached !== null) {
                $results[$ip] = $cached;
                $this->rememberRequestMemo($address, $cached);

                continue;
            }

            $misses[$ip] = $address;
        }

        if ($misses !== [] && ! $this->fakeMode) {
            $provider = $this->providerResolver->provider();

            if ($provider instanceof BatchIpProvider) {
                $batchResults = $provider->lookupMany(array_values($misses));

                foreach ($misses as $ip => $address) {
                    $providerResult = $batchResults[$address->value]
                        ?? ProviderResult::skipped('chain');

                    if ($providerResult->isHit()) {
                        $result = $this->finalizeProviderResult($address, $providerResult);
                        $results[$ip] = $result;
                        unset($misses[$ip]);
                    }
                }
            }
        }

        foreach ($misses as $ip => $address) {
            $results[$ip] = $this->lookup($address);
        }

        return $results;
    }

    /**
     * @param  list<string>  $ips
     */
    public function forManyQueued(array $ips): void
    {
        ProcessIpLookups::dispatch($ips);
    }

    public function lookup(IpAddress $address): IpInfoResult
    {
        $cached = $this->resolveFromCache($address);

        if ($cached !== null) {
            $this->rememberRequestMemo($address, $cached);

            if ($this->shouldDispatchEvents($address)) {
                $this->events->dispatch(new IpLookupStarted($address));
                $this->events->dispatch(new IpLookupCompleted($address, $cached));
            }

            return $cached;
        }

        if ($this->shouldUseRequestMemo() && isset($this->requestMemo[$address->value])) {
            return $this->requestMemo[$address->value];
        }

        if (! $this->shouldDispatchEvents($address)) {
            return $this->performLookup($address);
        }

        $this->events->dispatch(new IpLookupStarted($address));

        try {
            $result = $this->performLookup($address);
            $this->events->dispatch(new IpLookupCompleted($address, $result));

            return $result;
        } catch (Throwable $exception) {
            $this->events->dispatch(new IpLookupFailed($address, $exception));

            throw $exception;
        }
    }

    public function isPublic(IpAddress $address): bool
    {
        return $this->validator->isPublic($address->value);
    }

    public function isPrivate(IpAddress $address): bool
    {
        return $this->validator->shouldSkipExternalLookup($address->value);
    }

    public function normalizedIp(IpAddress $address): string
    {
        return $this->normalizer->normalize($address->value);
    }

    public function privacyProfile(IpAddress $address): IpPrivacyProfile
    {
        return $this->privacyInspector->profile($address->value);
    }

    public function threatSignals(IpAddress $address, ?IpThreatSignals $providerThreats = null): IpThreatSignals
    {
        $local = $this->threatInspector->inspect($address->value);

        if ($providerThreats === null) {
            return $local;
        }

        return $local->merge($providerThreats);
    }

    public function isFakeMode(): bool
    {
        return $this->fakeMode;
    }

    private function enableFakeProvider(FakeIpProvider $fake): void
    {
        $this->providerResolver->replace(new ChainProvider([$fake]));
        $this->fakeMode = true;
        $this->lastFake = $fake;
    }

    private function performLookup(IpAddress $address): IpInfoResult
    {
        $providerResult = $this->providerResolver->provider()->lookup($address);

        $result = $this->finalizeProviderResult($address, $providerResult);
        $this->rememberRequestMemo($address, $result);

        return $result;
    }

    private function finalizeProviderResult(IpAddress $address, ProviderResult $providerResult): IpInfoResult
    {
        $isPrivate = $this->isPrivate($address);
        $isPublic = $this->isPublic($address);
        $geo = $providerResult->geoLocation();
        $countryCode = $geo->countryCode;

        if (! $this->fakeMode && config('ip-info.cache.enabled', true) && $isPublic) {
            if ($countryCode !== null) {
                $this->cache->put($address, $countryCode);
            } elseif ($providerResult->shouldStopChain() && ! $providerResult->isHit()) {
                $this->cache->putNegative($address);
            }
        }

        return new IpInfoResult(
            $address->value,
            $geo,
            $isPublic,
            $isPrivate,
            $providerResult->provider,
            $this->threatSignals($address, $providerResult->threats),
        );
    }

    private function resolveFromCache(IpAddress $address): ?IpInfoResult
    {
        if ($this->fakeMode) {
            return null;
        }

        $isPrivate = $this->isPrivate($address);
        $isPublic = $this->isPublic($address);

        if (! config('ip-info.cache.enabled', true) || ! $isPublic) {
            return null;
        }

        if ($this->cache->hasNegative($address)) {
            return $this->makeResult($address, null, $isPublic, $isPrivate, 'cache:negative');
        }

        $cached = $this->cache->get($address);

        if ($cached === null) {
            return null;
        }

        return $this->makeResult($address, $cached, $isPublic, $isPrivate, 'cache');
    }

    private function shouldUseRequestMemo(): bool
    {
        return (bool) config('ip-info.lookup.request_memo', true);
    }

    private function shouldDispatchEvents(IpAddress $address): bool
    {
        if ($this->isPrivate($address) && config('ip-info.privacy.skip_private_ips', true)) {
            return false;
        }

        return true;
    }

    private function rememberRequestMemo(IpAddress $address, IpInfoResult $result): void
    {
        if ($this->shouldUseRequestMemo()) {
            $this->requestMemo[$address->value] = $result;
        }
    }

    private function makeResult(
        IpAddress $address,
        ?string $countryCode,
        bool $isPublic,
        bool $isPrivate,
        ?string $provider,
    ): IpInfoResult {
        return new IpInfoResult(
            $address->value,
            GeoLocation::fromCountryCode($countryCode),
            $isPublic,
            $isPrivate,
            $provider,
            $this->threatInspector->inspect($address->value),
        );
    }
}
