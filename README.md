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

```php
$country = client_country();              // helper
$country = ip_info()->countryCode();        // fluent
$country = request()->clientCountry();      // macro

if (ip_info()->isCountry('UA')) {
    // ...
}
```

Classic facade API still works:

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$country = IpInfo::for('8.8.8.8')->countryCode();
$client = IpInfo::forRequest(request())->countryCode();
```

Production (Cloudflare):

```bash
php artisan ip-info:install --preset=cloudflare
php artisan ip-info:refresh-cloudflare-cidrs --write-env-snippet
# paste IP_INFO_TRUSTED_PROXY_CIDRS into .env
# .env: IP_INFO_PRESET=cloudflare
```

Testing:

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Testing\InteractsWithIpInfo;

class ExampleTest extends TestCase
{
    use InteractsWithIpInfo;

    public function test_country(): void
    {
        $this->fakeIpInfo(['203.0.113.1' => 'UA']);

        $this->assertSame('UA', IpInfo::for('203.0.113.1')->countryCode());
    }
}
```

Middleware (Cloudflare preset):

```bash
php artisan vendor:publish --tag=ip-info-middleware
# Configure trusted proxy CIDRs, e.g. IP_INFO_TRUSTED_PROXY_CIDRS=173.245.48.0/20,...
# .env: IP_INFO_PRESET=cloudflare
```

## Security model (client IP)

- **Never trust `X-Forwarded-For` blindly.** Headers are read only when the remote address matches `trusted_proxies.proxy_cidrs`, unless `require_trusted_proxy_for_headers=false`.
- **Mirror Laravel `TrustProxies`** when using `respect_laravel=true` (default). Set `trusted_proxies.sync_with_laravel` as a reminder to keep both in sync.
- **Cloudflare presets** require `IP_INFO_TRUSTED_PROXY_CIDRS` (Cloudflare egress ranges) or network-level restriction. Presets do not embed rotating Cloudflare CIDRs.
- **HTTP providers** use HTTPS by default. Insecure `http://` URLs require `IP_INFO_HTTP_ALLOW_INSECURE=true`.

## Testing / fake behavior

`IpInfo::fake()` and `fakeSequence()`:

- Replace the provider chain for the current manager instance.
- **Bypass positive and negative cache** — tests stay deterministic even when cache is populated.
- Do **not** write lookup results to production cache while fake mode is active.

```php
IpInfo::fake(['8.8.8.8' => 'UA']); // overrides cached US for 8.8.8.8
IpInfo::assertLookedUp('8.8.8.8');
```

## Batch lookup

`IpInfo::forMany()` resolves cache hits first, then uses `BatchIpProvider::lookupMany()` on the chain when available (offline DB uses a single bounded SQL query). Remaining misses fall back to per-IP chain lookup.

## Architecture

| Layer | Role |
|-------|------|
| `IpInfoManager` | Orchestration, cache, events, fake mode |
| `IpProviderResolver` | Injectable provider access (no service locator) |
| `ChainProvider` | Ordered providers; stops on `Hit`/`Miss` |
| `ProviderStatus` | Explicit `skipped` / `failed` / `miss` / `hit` |
| `IpCache` | Positive + negative TTL cache |
| Custom providers | `providers.custom` or `IpInfoBuildingChain` event |

Privacy/logging policy lives in `IpPrivacyPolicy` — DTOs do not read Laravel config.

## What this package does

- Normalizes and validates IPv4/IPv6 input.
- Classifies public, private, localhost, link-local, and reserved addresses.
- Resolves client IP from HTTP requests using Laravel trusted proxy behavior by default.
- Looks up country codes through a provider chain:
  - `LocalProvider` — private/local/reserved IPs (no external call)
  - `DatabaseRangeProvider` — optional offline IPv4 ranges
  - `MaxMindProvider` — optional offline GeoLite2 MMDB
  - `HttpIpProvider` — optional HTTP drivers (`ip-api`, `ipinfo`)
  - `CleanTalkProvider` — optional HTTP fallback
- Caches public IP lookups via Laravel cache stores (with negative cache).
- Optional HTTP endpoint, middleware, and Artisan commands.

## Requirements

- PHP ^8.2
- Laravel ^10, ^11, or ^12
- Laravel cache (array, file, redis, etc.)
- Database optional (offline IPv4 lookup)
- `maxmind-db/reader` optional (MaxMind MMDB lookup)

## Installation

```bash
composer require suprun-bohdan/laravel-ip-info
php artisan ip-info:install
php artisan ip-info:install --preset=cloudflare
php artisan ip-info:install --with-database
```

Publish configuration only:

```bash
php artisan vendor:publish --tag=ip-info-config
```

Optional offline database:

```bash
# .env: IP_INFO_DATABASE_ENABLED=true
php artisan ip-info:install-database
```

Optional MaxMind GeoLite2:

```bash
# .env: IP_INFO_MAXMIND_ENABLED=true, IP_INFO_MAXMIND_LICENSE_KEY=...
composer require maxmind-db/reader
php artisan ip-info:update-maxmind
```

Diagnostics:

```bash
php artisan ip-info:diagnose
php artisan ip-info:diagnose 8.8.8.8 --json
```

## Application sync

Audit how the package is integrated into your Laravel app:

```bash
php artisan ip-info:sync
php artisan ip-info:sync --json
php artisan ip-info:sync --fix
php artisan ip-info:sync --publish-config --publish-middleware
```

`--fix` only performs **safe** actions: publish missing config/middleware stubs and run migrations. It does **not** edit `bootstrap/app.php`, `Kernel.php`, or `.env`. Preset recommendations are report-only.

When routes are enabled, configure protection:

```env
IP_INFO_ROUTES_ENABLED=true
IP_INFO_ROUTE_MIDDLEWARE=throttle:60,1
```

## Configuration

File: `config/ip-info.php`

| Section | Purpose |
|---------|---------|
| `cache` | Enable/disable cache, store, TTL, negative TTL, key prefix |
| `providers.chain` | Provider order: `local`, `database`, `maxmind`, `http`, `cleantalk` |
| `presets` | Named proxy/provider presets (`cloudflare`, `nginx_proxy`, `local_only`) |
| `maxmind` | GeoLite2 MMDB path and license key |
| `http` | HTTP driver selection, HTTPS enforcement, timeouts |
| `trusted_proxies` | Header allowlist, proxy CIDRs, Laravel fallback |
| `routes` | Opt-in endpoint path and route middleware |
| `privacy` | Logging policy via `IpPrivacyPolicy` |

See [docs/migration-guide.md](docs/migration-guide.md) and [docs/roadmap-v3.md](docs/roadmap-v3.md).

## Helpers and route aliases

| API | Example |
|-----|---------|
| `client_country()` | `client_country(default: 'XX')` |
| `ip_info()` | `ip_info('8.8.8.8')->isCountry('US')` |
| `geo.block:RU,BY` | Route middleware alias |
| `geo.allow:UA,PL` | Allow-list middleware alias |
| `ip.resolve` | Attach `ip_info` to request attributes |
| `geo.share` | Share `ClientGeoData` / Inertia `geo` prop |

Blade: `@country('UA')`, `@unlesscountry('RU')`, `@clientcountry('XX')`.

## Commands

| Command | Description |
|---------|-------------|
| `ip-info:install` | Publish config, migrate, optional preset/database |
| `ip-info:install --quick` | Enable HTTP geo via `quick_start` preset |
| `ip-info:install --register-middleware` | Register `ResolveClientIp` (opt-in) |
| `ip-info:refresh-cloudflare-cidrs` | Fetch Cloudflare egress CIDRs for `.env` |
| `ip-info:install-database` | Download IPv4 CSV and seed offline DB |
| `ip-info:update-database` | Refresh offline CSV database |
| `ip-info:update-maxmind` | Download GeoLite2-Country MMDB |
| `ip-info:sync` | Audit config/middleware/routes/security integration |
| `ip-info:sync --fix` | Safe publish/migrate only |
| `ip-info:diagnose` | Provider/runtime health + optional IP lookup |
| `ip-info:starter` | Publish starter middleware/config bundle |

## Development

Local Docker sandbox (gitignored):

```bash
cd sandbox && make init && make test
make test-package   # PHPUnit + PHPStan in package root
```

## License

MIT. See [LICENSE](LICENSE).
