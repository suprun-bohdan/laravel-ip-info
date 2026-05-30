<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Cache;

use Illuminate\Contracts\Cache\Repository;
use SuprunBohdan\IpInfo\Contracts\IpCache;
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

    public function forget(IpAddress $ip): void
    {
        $this->repository->forget($this->key($ip));
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
        return $this->prefix.':v1:'.$ip->value;
    }

    private function negativeKey(IpAddress $ip): string
    {
        return $this->prefix.':neg:v1:'.$ip->value;
    }
}
