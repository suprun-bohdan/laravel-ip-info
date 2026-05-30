<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Testing\FakeIpProvider;

/**
 * @method static \SuprunBohdan\IpInfo\Laravel\IpInfoQuery for(string $ip)
 * @method static \SuprunBohdan\IpInfo\Laravel\IpInfoQuery forRequest(\Illuminate\Http\Request $request)
 * @method static array<string, \SuprunBohdan\IpInfo\Data\IpInfoResult> forMany(list<string> $ips)
 * @method static void forManyQueued(list<string> $ips)
 * @method static FakeIpProvider fake(array<string, string|null> $map = [])
 * @method static FakeIpProvider fakeSequence(list<string|null> $sequence)
 * @method static void assertLookedUp(string $ip)
 * @method static \SuprunBohdan\IpInfo\Laravel\IpInfoManager withCachePrefix(string $prefix)
 *
 * @see IpInfoManager
 */
final class IpInfo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ip-info';
    }
}
