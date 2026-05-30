<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupCompleted;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupFailed;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupStarted;
use SuprunBohdan\IpInfo\Providers\ChainProvider;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Resolvers\StringIpResolver;
use SuprunBohdan\IpInfo\Support\IpValidator;
use SuprunBohdan\IpInfo\Testing\FakeIpProvider;
use Throwable;

final class IpInfoManager implements IpLookupContract
{
    public function __construct(
        private StringIpResolver $stringResolver,
        private RequestIpResolver $requestResolver,
        private IpValidator $validator,
        private IpCache $cache,
        private Dispatcher $events,
    ) {}

    /**
     * @param  array<string, string|null>  $map
     */
    public function fake(array $map = []): FakeIpProvider
    {
        $fake = new FakeIpProvider($map);
        app()->instance(IpProvider::class, new ChainProvider([$fake]));

        return $fake;
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

        foreach ($ips as $ip) {
            $results[$ip] = $this->for($ip)->result();
        }

        return $results;
    }

    public function lookup(IpAddress $address): IpInfoResult
    {
        $this->events->dispatch(new IpLookupStarted($address));

        try {
            $isPrivate = $this->isPrivate($address);
            $isPublic = $this->isPublic($address);

            if (config('ip-info.cache.enabled', true) && $isPublic) {
                if ($this->cache->hasNegative($address)) {
                    $result = $this->makeResult($address, null, $isPublic, $isPrivate, 'cache:negative');
                    $this->events->dispatch(new IpLookupCompleted($address, $result));

                    return $result;
                }

                $cached = $this->cache->get($address);

                if ($cached !== null) {
                    $result = $this->makeResult($address, $cached, $isPublic, $isPrivate, 'cache');
                    $this->events->dispatch(new IpLookupCompleted($address, $result));

                    return $result;
                }
            }

            $providerResult = app(IpProvider::class)->lookup($address);
            $geo = $providerResult->geoLocation();
            $countryCode = $geo->countryCode;

            if (config('ip-info.cache.enabled', true) && $isPublic) {
                if ($countryCode !== null) {
                    $this->cache->put($address, $countryCode);
                } elseif ($providerResult->resolved) {
                    $this->cache->putNegative($address);
                }
            }

            $result = new IpInfoResult(
                $address->value,
                $geo,
                $isPublic,
                $isPrivate,
                $providerResult->provider,
            );

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
        );
    }
}
