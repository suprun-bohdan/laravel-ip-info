<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Public IP country lookups are cached via Laravel cache stores.
    | Private/local/reserved addresses are never cached.
    |
    | Env: IP_INFO_CACHE_ENABLED, IP_INFO_CACHE_STORE,
    |      IP_INFO_CACHE_TTL, IP_INFO_CACHE_PREFIX
    */
    'cache' => [
        'enabled' => env('IP_INFO_CACHE_ENABLED', true),
        'store' => env('IP_INFO_CACHE_STORE', null),
        'ttl' => (int) env('IP_INFO_CACHE_TTL', 86400),
        'prefix' => env('IP_INFO_CACHE_PREFIX', 'laravel_ip_info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | chain — ordered built-in provider names resolved by the service provider.
    | custom — additional container-resolvable IpProvider class names.
    */
    'providers' => [
        'default' => 'chain',
        'chain' => ['local', 'database', 'cleantalk'],
        'custom' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | CleanTalk HTTP provider
    |--------------------------------------------------------------------------
    |
    | Disabled by default. URL host must remain api.cleantalk.org (SSRF guard).
    |
    | Env: IP_INFO_CLEANTALK_ENABLED, IP_INFO_CLEANTALK_TIMEOUT
    */
    'cleantalk' => [
        'enabled' => env('IP_INFO_CLEANTALK_ENABLED', false),
        'timeout' => (int) env('IP_INFO_CLEANTALK_TIMEOUT', 3),
        'url' => 'https://api.cleantalk.org/?method_name=ip_info&ip=%s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline IPv4 database
    |--------------------------------------------------------------------------
    |
    | Requires migration + ip-info:install-database when enabled.
    |
    | Env: IP_INFO_DATABASE_ENABLED
    */
    'database' => [
        'enabled' => env('IP_INFO_DATABASE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional HTTP route
    |--------------------------------------------------------------------------
    |
    | Bundled JSON endpoint; disabled by default.
    |
    | Env: IP_INFO_ROUTES_ENABLED, IP_INFO_ROUTE_PATH
    */
    'routes' => [
        'enabled' => env('IP_INFO_ROUTES_ENABLED', false),
        'path' => env('IP_INFO_ROUTE_PATH', '/ip-info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies / client IP headers
    |--------------------------------------------------------------------------
    |
    | respect_laravel — use Laravel $request->ip() when no trusted header matches.
    | headers — only honor these request headers when explicitly configured.
    |
    | Env: IP_INFO_RESPECT_LARAVEL_PROXIES
    */
    'trusted_proxies' => [
        'respect_laravel' => env('IP_INFO_RESPECT_LARAVEL_PROXIES', true),
        'headers' => [],
    ],
];
