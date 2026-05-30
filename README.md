# Laravel IP Info

[![Latest Version on Packagist](https://img.shields.io/packagist/v/suprun-bohdan/laravel-ip-info.svg?style=flat-square)](https://packagist.org/packages/suprun-bohdan/laravel-ip-info)
[![Tests](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml)
[![Benchmarks](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Laravel package for IP detection, normalization, request IP resolution, geo lookup, caching, and infrastructure-aware IP intelligence.

Author: [Bohdan Suprun](mailto:bohdan-suprun@outlook.com)

## Quick Start

```bash
composer require suprun-bohdan/laravel-ip-info
php artisan ip-info:install --quick --register-middleware --force
```

After install, public IP lookups work immediately via the HTTP provider (`quick_start` preset). For production, prefer MaxMind or the offline IPv4 database.

```php
// Helpers (v4.2+)
$country = client_country();
$country = client_country(default: 'XX');

// Fluent
if (ip_info()->isCountry('UA', 'PL')) {
    // ...
}

// Request macros
$country = request()->clientCountry();
$ip = request()->clientIp();

// Classic facade
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

IpInfo::for('8.8.8.8')->countryCode();
IpInfo::forRequest(request())->countryCode();
```

### Production behind Cloudflare

```bash
php artisan ip-info:install --preset=cloudflare
php artisan ip-info:refresh-cloudflare-cidrs --write-env-snippet
```

Add to `.env`:

```env
IP_INFO_PRESET=cloudflare
IP_INFO_TRUSTED_PROXY_CIDRS=173.245.48.0/20,...
```

Register client IP resolution (opt-in):

```bash
php artisan ip-info:install --register-middleware --force
# or: php artisan ip-info:sync --register-middleware --force
```

## Route middleware (v4.2+)

```php
Route::middleware(['ip.resolve', 'geo.block:RU,BY'])->group(function () {
    // ResolveClientIp + block listed countries
});

Route::middleware('geo.allow:UA,PL')->group(function () {
    // Allow-list only
});

Route::middleware('geo.share')->group(function () {
    // Shares ClientGeoData; Inertia apps get shared prop `geo`
});
```

| Alias | Class | Purpose |
|-------|-------|---------|
| `ip.resolve` | `ResolveClientIp` | Sets `ip_info` / `client_ip` on request |
| `geo.block` | `BlockCountries` | Block by country (`geo.block:RU,BY`) |
| `geo.allow` | `AllowCountries` | Allow-list only |
| `geo.share` | `ShareClientGeo` | API/Inertia geo payload |
| `ip.log` | `LogClientIp` | Structured client IP logging (v4.3+) |
| `ip.filter` | `FilterClientIp` | Block Tor/proxy/WHOIS rules (v4.3+) |

```php
Route::middleware(['ip.resolve', 'ip.log', 'ip.filter'])->group(function () {
    // Resolve + log + filter (Tor, proxy, WHOIS netname/ASN, etc.)
});
```

## IP intelligence (v4.3+)

Live WHOIS (IANA → RIR, TCP :43), threat signals, privacy profile, and optional request logging:

```php
$intel = client_ip_intel(withWhois: true);
$intel->geo->countryCode();
$intel->whois?->netname;
$intel->whois?->originAsn;

php artisan ip-info:whois 8.8.8.8
```

Key env vars: `IP_INFO_WHOIS_ENABLED`, `IP_INFO_CLIENT_LOG_ENABLED`, `IP_INFO_FILTERING_ENABLED`, `IP_INFO_TOR_EXIT_CIDRS`.

## Blade (v4.2+)

```blade
@country('UA', 'PL')
    Content for Ukraine or Poland
@endcountry

@unlesscountry('RU')
    Hidden for Russia
@endunlesscountry

Country: @clientcountry('XX')
```

## Helpers reference (v4.2+)

| Function / macro | Returns | Notes |
|------------------|---------|-------|
| `ip_info(?string $ip = null)` | `IpInfoQuery` | Current request when `$ip` omitted |
| `client_country(?Request $r = null, ?string $default = null)` | `?string` | Uses request memo + cache |
| `client_ip(?Request $r = null)` | `string` | Reuses middleware attribute when set |
| `client_ip_info(?Request $r = null)` | `IpInfoResult` | Full DTO |
| `normalize_ip(string $ip)` | `string` | Canonical IP (v4.3+) |
| `ip_privacy(?string $ip = null)` | `IpPrivacyProfile` | Private/public/reserved (v4.3+) |
| `ip_threats(?string $ip = null)` | `IpThreatSignals` | Tor/proxy/VPN/hosting (v4.3+) |
| `client_ip_intel(?Request $r = null, bool $withWhois = false)` | `ClientIpIntel` | Geo + privacy + threats + WHOIS (v4.3+) |
| `whois_lookup(string $ip, bool $force = false)` | `?WhoisRecord` | Live WHOIS lookup (v4.3+) |
| `request()->ipInfo()` | `IpInfoResult` | Macro |
| `request()->isCountry('UA', ...)` | `bool` | Macro |

Fluent on `IpInfoQuery` / `IpInfoResult`: `isCountry()`, `inCountries()`, `isEu()`, `countryOr()`, `countryOrFail()`.

## Security model (client IP)

- **Never trust `X-Forwarded-For` blindly.** Headers are read only when the remote address matches `trusted_proxies.proxy_cidrs`, unless `require_trusted_proxy_for_headers=false`.
- **Mirror Laravel `TrustProxies`** when using `respect_laravel=true` (default).
- **Cloudflare presets** require `IP_INFO_TRUSTED_PROXY_CIDRS`. Refresh with `ip-info:refresh-cloudflare-cidrs`.
- **Runtime presets** — set `IP_INFO_PRESET=cloudflare|nginx_proxy|quick_start|local_only` (merged on each boot).
- **HTTP providers** use HTTPS by default. Insecure `http://` URLs require `IP_INFO_HTTP_ALLOW_INSECURE=true`.

## Testing

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Testing\InteractsWithIpInfo;

class ExampleTest extends TestCase
{
    use InteractsWithIpInfo;

    public function test_country(): void
    {
        $this->fakeIpInfo(['203.0.113.1' => 'UA']);

        $this->assertSame('UA', client_country());
        $this->assertTrue(ip_info('203.0.113.1')->isCountry('UA'));
    }
}
```

`IpInfo::fake()` bypasses positive/negative cache and skips cache writes during tests.

## What this package does

- Normalizes and validates IPv4/IPv6 input.
- Classifies public, private, localhost, link-local, and reserved addresses.
- Resolves client IP from HTTP requests with trusted-proxy awareness.
- Looks up country codes through a provider chain (`local`, `database`, `maxmind`, `http`, `cleantalk`).
- Caches lookups with configurable negative TTL.
- Geo block/allow middleware, validation rules, sync/diagnose tooling.

## Requirements

- PHP ^8.2
- Laravel ^10, ^11, or ^12
- Laravel cache (array, file, redis, etc.)
- Database optional (offline IPv4 lookup)
- `maxmind-db/reader` optional (MaxMind GeoLite2)

## Installation options

```bash
composer require suprun-bohdan/laravel-ip-info

# Recommended for first run — HTTP geo works immediately
php artisan ip-info:install --quick

# Production presets
php artisan ip-info:install --preset=cloudflare
php artisan ip-info:install --preset=nginx_proxy
php artisan ip-info:install --with-database

# Optional automation
php artisan ip-info:install --register-middleware --force
php artisan ip-info:install --with-schedule
php artisan ip-info:starter --quick --register-middleware --force
```

Publish configuration only:

```bash
php artisan vendor:publish --tag=ip-info-config
```

Optional offline database:

```bash
# .env: IP_INFO_DATABASE_ENABLED=true
php artisan ip-info:install-database
php artisan ip-info:update-database
```

Optional MaxMind GeoLite2:

```bash
# .env: IP_INFO_MAXMIND_ENABLED=true, IP_INFO_MAXMIND_LICENSE_KEY=...
composer require maxmind-db/reader
php artisan ip-info:update-maxmind
```

## Application sync

```bash
php artisan ip-info:sync
php artisan ip-info:sync --json
php artisan ip-info:sync --fix
php artisan ip-info:sync --register-middleware --force
php artisan ip-info:sync --with-schedule
```

`--fix` performs **safe** actions only: publish missing stubs and migrate. Middleware registration and schedule stubs require explicit flags.

When routes are enabled:

```env
IP_INFO_ROUTES_ENABLED=true
IP_INFO_ROUTE_MIDDLEWARE=throttle:60,1
```

## Configuration

File: `config/ip-info.php`

| Section | Purpose |
|---------|---------|
| `active_preset` / `IP_INFO_PRESET` | Runtime preset merge (`cloudflare`, `quick_start`, …) |
| `cache` | TTL, negative cache, prefix, tenant prefix |
| `providers.chain` | Provider order |
| `presets` | Named proxy/provider bundles |
| `security` | Blocked/allowed countries, response status/message |
| `maxmind` | GeoLite2 MMDB path and license key |
| `http` | Driver (`ipinfo`, `ip-api`), HTTPS enforcement |
| `trusted_proxies` | Header allowlist, proxy CIDRs |
| `routes` | Opt-in API endpoint |
| `privacy` | Logging policy via `IpPrivacyPolicy` |

## Commands

| Command | Description |
|---------|-------------|
| `ip-info:install` | Publish config, migrate, optional preset |
| `ip-info:install --quick` | Enable HTTP geo (`quick_start` preset) |
| `ip-info:install --register-middleware` | Register `ResolveClientIp` (opt-in) |
| `ip-info:install --with-schedule` | Append update stubs to `routes/console.php` |
| `ip-info:refresh-cloudflare-cidrs` | Fetch Cloudflare egress CIDRs for `.env` |
| `ip-info:sync` | Audit integration (config, middleware, routes, security) |
| `ip-info:diagnose` | Provider health + optional IP lookup |
| `ip-info:about` | Capability matrix (preset, helpers, aliases) |
| `ip-info:starter` | Publish middleware + install bundle |
| `ip-info:update-database` | Refresh offline IPv4 CSV |
| `ip-info:update-maxmind` | Download GeoLite2-Country MMDB |

## Architecture

| Layer | Role |
|-------|------|
| `IpInfoManager` | Orchestration, cache, events, fake mode |
| `IpProviderResolver` | Injectable provider access |
| `ChainProvider` | Ordered providers; stops on hit/miss |
| `PresetConfigurator` | Runtime preset merge from `.env` |
| `IpCache` | Positive + negative TTL cache |
| Custom providers | `providers.custom` or `IpInfoBuildingChain` event |

## Documentation

Public docs: [docs/README.md](docs/README.md)

- [Migration guide](docs/migration-guide.md) — upgrades and breaking changes
- [vs alternatives](docs/vs-alternatives.md) — comparison with other packages

## Development

```bash
composer test
composer analyse
composer format:test
```

Local Docker sandbox (gitignored): `cd sandbox && make init && make test`

## License

MIT. See [LICENSE](LICENSE).
