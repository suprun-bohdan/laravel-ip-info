<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

final class PackageStubs
{
    public const VERSION = '4.7.3';

    public const STUB_VERSION_PATTERN = '/@ip-info-stub-version\s+([\d.]+)/';

    public static function configStubPath(): string
    {
        return dirname(__DIR__, 2).'/config/ip-info.php';
    }

    public static function resolveClientIpMiddlewareStubPath(): string
    {
        return dirname(__DIR__).'/Http/Middleware/ResolveClientIp.php';
    }

    public static function blockCountriesMiddlewareStubPath(): string
    {
        return dirname(__DIR__).'/Http/Middleware/BlockCountries.php';
    }

    public static function allowCountriesMiddlewareStubPath(): string
    {
        return dirname(__DIR__).'/Http/Middleware/AllowCountries.php';
    }
}
