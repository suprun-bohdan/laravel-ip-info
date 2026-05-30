<?php

// @ip-info-stub-version 4.3.0

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
        'quick_start' => [
            'providers' => [
                'chain' => ['local', 'http', 'null'],
            ],
            'http' => [
                'enabled' => true,
                'driver' => 'ipinfo',
            ],
            'cleantalk' => ['enabled' => false],
            'database' => ['enabled' => false],
            'maxmind' => ['enabled' => false],
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
        'block_response_status' => (int) env('IP_INFO_BLOCK_RESPONSE_STATUS', 403),
        'block_response_message' => env('IP_INFO_BLOCK_RESPONSE_MESSAGE', 'Access from your country is not allowed.'),
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
        'enrich_threat_signals' => env('IP_INFO_HTTP_ENRICH_THREAT', true),
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
    | Threat intelligence (Tor / proxy / hosting CIDR lists)
    |--------------------------------------------------------------------------
    */
    'threat_intel' => [
        'enabled' => env('IP_INFO_THREAT_INTEL_ENABLED', true),
        'tor_exit_cidrs' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_TOR_EXIT_CIDRS', ''))
        ))),
        'known_proxy_cidrs' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_KNOWN_PROXY_CIDRS', ''))
        ))),
        'hosting_cidrs' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_HOSTING_CIDRS', ''))
        ))),
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

    'filtering' => [
        'enabled' => env('IP_INFO_FILTERING_ENABLED', false),
        'block_tor' => env('IP_INFO_FILTER_BLOCK_TOR', false),
        'block_proxy' => env('IP_INFO_FILTER_BLOCK_PROXY', false),
        'block_vpn' => env('IP_INFO_FILTER_BLOCK_VPN', false),
        'block_hosting' => env('IP_INFO_FILTER_BLOCK_HOSTING', false),
        'blocked_countries' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_FILTER_BLOCKED_COUNTRIES', ''))
        ))),
        'blocked_netnames' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_FILTER_BLOCKED_NETNAMES', ''))
        ))),
        'blocked_organizations' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_FILTER_BLOCKED_ORGS', ''))
        ))),
        'blocked_origin_asns' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IP_INFO_FILTER_BLOCKED_ASNS', ''))
        ))),
        'require_whois_country_match' => env('IP_INFO_FILTER_WHOIS_COUNTRY_MATCH', false),
        'block_response_status' => (int) env('IP_INFO_FILTER_RESPONSE_STATUS', 403),
        'block_response_message' => env('IP_INFO_FILTER_RESPONSE_MESSAGE', 'Access from your network is not allowed.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Client IP logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('IP_INFO_CLIENT_LOG_ENABLED', false),
        'channel' => env('IP_INFO_CLIENT_LOG_CHANNEL'),
        'level' => env('IP_INFO_CLIENT_LOG_LEVEL', 'info'),
        'message' => env('IP_INFO_CLIENT_LOG_MESSAGE', 'Client IP intelligence'),
        'include_whois' => env('IP_INFO_CLIENT_LOG_INCLUDE_WHOIS', true),
        'include_threats' => env('IP_INFO_CLIENT_LOG_INCLUDE_THREATS', true),
        'auto_log_on_lookup' => env('IP_INFO_CLIENT_LOG_ON_LOOKUP', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | WHOIS lookups (live TCP queries via IANA referral)
    |--------------------------------------------------------------------------
    */
    'whois' => [
        'enabled' => env('IP_INFO_WHOIS_ENABLED', false),
        'timeout' => (int) env('IP_INFO_WHOIS_TIMEOUT', 5),
        'cache_ttl' => (int) env('IP_INFO_WHOIS_CACHE_TTL', 86400),
        'max_referrals' => (int) env('IP_INFO_WHOIS_MAX_REFERRALS', 2),
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

    /*
    |--------------------------------------------------------------------------
    | Active preset (runtime)
    |--------------------------------------------------------------------------
    |
    | When set, preset values are merged into runtime config on each boot.
    | Use IP_INFO_PRESET in .env (install_preset is a deprecated alias).
    |
    */
    'active_preset' => env('IP_INFO_PRESET', env('IP_INFO_ACTIVE_PRESET')),

    /** @deprecated Use active_preset / IP_INFO_PRESET instead. */
    'install_preset' => env('IP_INFO_PRESET'),
];
