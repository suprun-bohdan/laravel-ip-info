<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;

/**
 * @method static \SuprunBohdan\IpInfo\Laravel\IpInfoQuery for(string $ip)
 * @method static \SuprunBohdan\IpInfo\Laravel\IpInfoQuery forRequest(\Illuminate\Http\Request $request)
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
