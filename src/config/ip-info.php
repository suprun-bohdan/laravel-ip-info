<?php

// @ip-info-stub-version 4.1.0

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('IP_INFO_CACHE_ENABLED', true),
        'store' => env('IP_INFO_CACHE_STORE', null),
        'ttl' => (int) env('IP_INFO_CACHE_TTL', 86400),
        'negative_ttl' => (int) env('IP_INFO_CACHE_NEGATIVE_TTL', 300),
        'prefix' => env('IP_INFO_CACHE_PREFIX', 'laravel_ip_info'),
        'tenant_prefix' => env('IP_INFO_TENANT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lookup behavior
    |--------------------------------------------------------------------------
    */
    'lookup' => [
        'request_memo' => env('IP_INFO_REQUEST_MEMO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
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
    */
    'presets' => [
        'cloudflare' => [
            'trusted_proxies' => [
                'respect_laravel' => false,
                'headers' => ['CF-Connecting-IP'],
                'require_trusted_proxy_for_headers' => true,
                'proxy_cidrs' => [],
            ],
        ],
        'cloudflare_strict' => [
            'trusted_proxies' => [
                'respect_laravel' => false,
                'headers' => ['CF-Connecting-IP'],
                'require_trusted_proxy_for_headers' => true,
                'proxy_cidrs' => [],
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
    | Geo security (middleware helpers)
    |--------------------------------------------------------------------------
    */
    'security' => [
        'blocked_countries' => array_filter(explode(',', (string) env('IP_INFO_BLOCKED_COUNTRIES', ''))),
        'allowed_countries' => array_filter(explode(',', (string) env('IP_INFO_ALLOWED_COUNTRIES', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | CleanTalk HTTP provider
    |--------------------------------------------------------------------------
    */
    'cleantalk' => [
        'enabled' => env('IP_INFO_CLEANTALK_ENABLED', false),
        'timeout' => (int) env('IP_INFO_CLEANTALK_TIMEOUT', 3),
        'url' => 'https://api.cleantalk.org/?method_name=ip_info&ip=%s',
        'retries' => (int) env('IP_INFO_CLEANTALK_RETRIES', 1),
        'soft_fail_statuses' => [429, 500, 502, 503, 504],
        'circuit_breaker' => [
            'enabled' => env('IP_INFO_CLEANTALK_CIRCUIT_BREAKER', true),
            'failure_threshold' => (int) env('IP_INFO_CLEANTALK_CIRCUIT_THRESHOLD', 5),
            'ttl' => (int) env('IP_INFO_CLEANTALK_CIRCUIT_TTL', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline IPv4 database
    |--------------------------------------------------------------------------
    */
    'database' => [
        'enabled' => env('IP_INFO_DATABASE_ENABLED', false),
        'stale_days' => (int) env('IP_INFO_DATABASE_STALE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | MaxMind GeoLite2
    |--------------------------------------------------------------------------
    */
    'maxmind' => [
        'enabled' => env('IP_INFO_MAXMIND_ENABLED', false),
        'license_key' => env('IP_INFO_MAXMIND_LICENSE_KEY'),
        'database_path' => env('IP_INFO_MAXMIND_DATABASE_PATH', storage_path('app/geoip/GeoLite2-Country.mmdb')),
        'stale_days' => (int) env('IP_INFO_MAXMIND_STALE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP geo providers
    |--------------------------------------------------------------------------
    */
    'http' => [
        'enabled' => env('IP_INFO_HTTP_ENABLED', false),
        'driver' => env('IP_INFO_HTTP_DRIVER', 'ipinfo'),
        'allow_insecure' => env('IP_INFO_HTTP_ALLOW_INSECURE', false),
        'timeout' => (int) env('IP_INFO_HTTP_TIMEOUT', 3),
        'retries' => (int) env('IP_INFO_HTTP_RETRIES', 1),
        'soft_fail_statuses' => [429, 500, 502, 503, 504],
        'circuit_breaker' => [
            'enabled' => env('IP_INFO_HTTP_CIRCUIT_BREAKER', true),
            'failure_threshold' => (int) env('IP_INFO_HTTP_CIRCUIT_THRESHOLD', 5),
            'ttl' => (int) env('IP_INFO_HTTP_CIRCUIT_TTL', 60),
        ],
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
    */
    'privacy' => [
        'log_lookups' => env('IP_INFO_PRIVACY_LOG_LOOKUPS', true),
        'skip_private_ips' => env('IP_INFO_PRIVACY_SKIP_PRIVATE', true),
        'redact_headers' => env('IP_INFO_PRIVACY_REDACT_HEADERS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies / client IP headers
    |--------------------------------------------------------------------------
    */
    'trusted_proxies' => [
        'respect_laravel' => env('IP_INFO_RESPECT_LARAVEL_PROXIES', true),
        'sync_with_laravel' => env('IP_INFO_SYNC_TRUSTED_PROXIES', false),
        'headers' => [],
        'proxy_cidrs' => array_filter(explode(',', (string) env('IP_INFO_TRUSTED_PROXY_CIDRS', ''))),
        'require_trusted_proxy_for_headers' => env('IP_INFO_REQUIRE_TRUSTED_PROXY', true),
    ],

    'pulse' => [
        'enabled' => env('IP_INFO_PULSE_ENABLED', true),
    ],

    'telescope' => [
        'enabled' => env('IP_INFO_TELESCOPE_ENABLED', true),
    ],

    'routes' => [
        'enabled' => env('IP_INFO_ROUTES_ENABLED', false),
        'path' => env('IP_INFO_ROUTE_PATH', '/ip-info'),
        'middleware' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_ROUTE_MIDDLEWARE', ''))
        ))),
    ],

    'install_preset' => env('IP_INFO_PRESET'),
];
