<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Resolvers\RequestIpResolver;
use SuprunBohdan\IpInfo\Resolvers\StringIpResolver;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class IpInfoManager implements IpLookupContract
{
    public function __construct(
        private StringIpResolver $stringResolver,
        private RequestIpResolver $requestResolver,
        private IpValidator $validator,
        private IpCache $cache,
        private IpProvider $provider,
    ) {}

    public function for(string $ip): IpInfoQuery
    {
        return new IpInfoQuery($this, $this->stringResolver->resolve($ip));
    }

    public function forRequest(Request $request): IpInfoQuery
    {
        return new IpInfoQuery($this, $this->requestResolver->resolve($request));
    }

    public function lookup(IpAddress $address): IpInfoResult
    {
        $isPrivate = $this->isPrivate($address);
        $isPublic = $this->isPublic($address);

        if (config('ip-info.cache.enabled', true) && $isPublic) {
            $cached = $this->cache->get($address);

            if ($cached !== null) {
                return $this->makeResult($address, $cached, $isPublic, $isPrivate, 'cache');
            }
        }

        $providerResult = $this->provider->lookup($address);
        $countryCode = $providerResult->countryCode;

        if (
            $countryCode !== null
            && config('ip-info.cache.enabled', true)
            && $isPublic
        ) {
            $this->cache->put($address, $countryCode);
        }

        return $this->makeResult(
            $address,
            $countryCode,
            $isPublic,
            $isPrivate,
            $providerResult->provider,
        );
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
            new GeoLocation($countryCode),
            $isPublic,
            $isPrivate,
            $provider,
        );
    }
}
