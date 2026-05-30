<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Cache;

use Illuminate\Support\Facades\Cache;
use SuprunBohdan\IpInfo\Contracts\IpCache;
use SuprunBohdan\IpInfo\Data\IpAddress;

final class LaravelCacheIpCache implements IpCache
{
    public function __construct(
        private ?string $store,
        private string $prefix,
        private int $ttl,
    ) {}

    public function get(IpAddress $ip): ?string
    {
        $value = Cache::store($this->store)->get($this->key($ip));

        return is_string($value) ? $value : null;
    }

    public function put(IpAddress $ip, string $countryCode): void
    {
        Cache::store($this->store)->put($this->key($ip), $countryCode, $this->ttl);
    }

    public function forget(IpAddress $ip): void
    {
        Cache::store($this->store)->forget($this->key($ip));
    }

    public function key(IpAddress $ip): string
    {
        return $this->prefix.':v1:'.$ip->value;
    }
}
