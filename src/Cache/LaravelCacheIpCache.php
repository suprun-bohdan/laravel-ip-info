<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Cache;

use Illuminate\Contracts\Cache\Repository;
use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;

final class LaravelCacheIpCache implements IpCache
{
    public function __construct(
        private Repository $repository,
        private string $prefix,
        private int $ttl,
        private int $negativeTtl,
    ) {}

    public function get(IpAddress $ip): ?string
    {
        $value = $this->repository->get($this->key($ip));

        return is_string($value) ? $value : null;
    }

    public function put(IpAddress $ip, string $countryCode): void
    {
        $this->repository->put($this->key($ip), $countryCode, $this->ttl);
        $this->forgetNegative($ip);
    }

    public function getGeo(IpAddress $ip): ?GeoLocation
    {
        $value = $this->repository->get($this->geoKey($ip));

        if (! is_string($value) || $value === '') {
            return null;
        }

        $payload = json_decode($value, true);

        if (! is_array($payload)) {
            return null;
        }

        return GeoLocation::fromCachePayload($payload);
    }

    public function putGeo(IpAddress $ip, GeoLocation $geo): void
    {
        $payload = $geo->toCachePayload();

        if ($payload === []) {
            return;
        }

        $this->repository->put(
            $this->geoKey($ip),
            json_encode($payload, JSON_THROW_ON_ERROR),
            $this->ttl,
        );
        $this->forgetNegative($ip);
    }

    public function forget(IpAddress $ip): void
    {
        $this->repository->forget($this->key($ip));
        $this->repository->forget($this->geoKey($ip));
        $this->forgetNegative($ip);
    }

    public function hasNegative(IpAddress $ip): bool
    {
        return $this->repository->has($this->negativeKey($ip));
    }

    public function putNegative(IpAddress $ip): void
    {
        $this->repository->put($this->negativeKey($ip), true, $this->negativeTtl);
    }

    public function forgetNegative(IpAddress $ip): void
    {
        $this->repository->forget($this->negativeKey($ip));
    }

    public function key(IpAddress $ip): string
    {
        return $this->effectivePrefix().':v1:'.$ip->value;
    }

    public function geoKey(IpAddress $ip): string
    {
        return $this->effectivePrefix().':v2:'.$ip->value;
    }

    private function negativeKey(IpAddress $ip): string
    {
        return $this->effectivePrefix().':neg:v1:'.$ip->value;
    }

    private function effectivePrefix(): string
    {
        $prefix = $this->prefix;

        if (function_exists('app') && app()->bound('ip-info.runtime_cache_prefix')) {
            $runtime = app('ip-info.runtime_cache_prefix');

            if (is_string($runtime) && $runtime !== '') {
                $prefix .= ':'.$runtime;
            }
        }

        $tenant = config('ip-info.cache.tenant_prefix');

        if (is_string($tenant) && $tenant !== '') {
            $prefix .= ':'.$tenant;
        }

        return $prefix;
    }
}
