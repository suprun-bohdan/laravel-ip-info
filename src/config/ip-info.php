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
    |      IP_INFO_CACHE_TTL, IP_INFO_CACHE_NEGATIVE_TTL, IP_INFO_CACHE_PREFIX
    */
    'cache' => [
        'enabled' => env('IP_INFO_CACHE_ENABLED', true),
        'store' => env('IP_INFO_CACHE_STORE', null),
        'ttl' => (int) env('IP_INFO_CACHE_TTL', 86400),
        'negative_ttl' => (int) env('IP_INFO_CACHE_NEGATIVE_TTL', 300),
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
        'chain' => ['local', 'database', 'maxmind', 'http', 'cleantalk'],
        'custom' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Named presets
    |--------------------------------------------------------------------------
    |
    | Applied via ip-info:install --preset= or IP_INFO_PRESET env at install time.
    */
    'presets' => [
        'cloudflare' => [
            'trusted_proxies' => [
                'respect_laravel' => false,
                'headers' => ['CF-Connecting-IP', 'X-Forwarded-For'],
            ],
        ],
        'nginx_proxy' => [
            'trusted_proxies' => [
                'respect_laravel' => true,
                'headers' => ['X-Forwarded-For', 'X-Real-IP'],
            ],
        ],
        'local_only' => [
            'providers' => [
                'chain' => ['local', 'null'],
            ],
            'cleantalk' => ['enabled' => false],
            'database' => ['enabled' => false],
            'maxmind' => ['enabled' => false],
            'http' => ['enabled' => false],
        ],
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
    | Env: IP_INFO_DATABASE_ENABLED, IP_INFO_DATABASE_STALE_DAYS
    */
    'database' => [
        'enabled' => env('IP_INFO_DATABASE_ENABLED', false),
        'stale_days' => (int) env('IP_INFO_DATABASE_STALE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | MaxMind GeoLite2
    |--------------------------------------------------------------------------
    |
    | Requires maxmind-db/reader and ip-info:update-maxmind when enabled.
    |
    | Env: IP_INFO_MAXMIND_ENABLED, IP_INFO_MAXMIND_LICENSE_KEY,
    |      IP_INFO_MAXMIND_DATABASE_PATH
    */
    'maxmind' => [
        'enabled' => env('IP_INFO_MAXMIND_ENABLED', false),
        'license_key' => env('IP_INFO_MAXMIND_LICENSE_KEY'),
        'database_path' => env('IP_INFO_MAXMIND_DATABASE_PATH', storage_path('app/geoip/GeoLite2-Country.mmdb')),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP geo providers
    |--------------------------------------------------------------------------
    |
    | Env: IP_INFO_HTTP_ENABLED, IP_INFO_HTTP_DRIVER, IP_INFO_HTTP_TIMEOUT
    */
    'http' => [
        'enabled' => env('IP_INFO_HTTP_ENABLED', false),
        'driver' => env('IP_INFO_HTTP_DRIVER', 'ip-api'),
        'timeout' => (int) env('IP_INFO_HTTP_TIMEOUT', 3),
        'drivers' => [
            'ip-api' => [
                'url' => 'http://ip-api.com/json/%s?fields=status,country,countryCode',
                'allowed_hosts' => ['ip-api.com'],
            ],
            'ipinfo' => [
                'url' => 'https://ipinfo.io/%s/json',
                'allowed_hosts' => ['ipinfo.io'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy helpers
    |--------------------------------------------------------------------------
    |
    | Env: IP_INFO_PRIVACY_LOG_LOOKUPS
    */
    'privacy' => [
        'log_lookups' => env('IP_INFO_PRIVACY_LOG_LOOKUPS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Pulse recorder
    |--------------------------------------------------------------------------
    |
    | Env: IP_INFO_PULSE_ENABLED
    */
    'pulse' => [
        'enabled' => env('IP_INFO_PULSE_ENABLED', true),
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

    /*
    |--------------------------------------------------------------------------
    | Install defaults
    |--------------------------------------------------------------------------
    |
    | Env: IP_INFO_PRESET
    */
    'install_preset' => env('IP_INFO_PRESET'),
];
