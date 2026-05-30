# Laravel IP Info

[![Latest Version on Packagist](https://img.shields.io/packagist/v/suprun-bohdan/laravel-ip-info.svg?style=flat-square)](https://packagist.org/packages/suprun-bohdan/laravel-ip-info)
[![Tests](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml)
[![Benchmarks](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Laravel package for IP detection, normalization, request IP resolution, geo lookup, caching, and infrastructure-aware IP intelligence.

**Author:** [Bohdan Suprun](mailto:bohdan-suprun@outlook.com)

## Contents

- [Quick start](#quick-start)
- [Core API](#core-api)
- [Security model (client IP)](#security-model-client-ip)
- [Middleware & validation](#middleware--validation)
- [Provider chain](#provider-chain)
- [Batch & queue](#batch--queue)
- [Testing](#testing)
- [Application sync](#application-sync)
- [Observability](#observability)
- [Configuration](#configuration)
- [Artisan commands](#artisan-commands)
- [Environment variables](#environment-variables)
- [Architecture](#architecture)
- [Documentation](#documentation)
- [Development](#development)
- [License](#license)

## Quick start

```bash
composer require suprun-bohdan/laravel-ip-info
php artisan ip-info:install
php artisan ip-info:about
```

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$country = IpInfo::for('8.8.8.8')->countryCode(); // "US"
$client = IpInfo::forRequest(request())->countryCode();
$result = IpInfo::for('8.8.8.8')->result();      // IpInfoResult DTO
```

Behind Cloudflare or another reverse proxy:

```bash
php artisan ip-info:install --preset=cloudflare
# Set Cloudflare egress CIDRs, e.g.:
# IP_INFO_TRUSTED_PROXY_CIDRS=173.245.48.0/20,103.21.244.0/22,...
# IP_INFO_PRESET=cloudflare
php artisan vendor:publish --tag=ip-info-middleware
```

Audit integration after install or upgrades:

```bash
php artisan ip-info:sync
php artisan ip-info:sync --json
```

## Core API

| Method | Description |
|--------|-------------|
| `IpInfo::for($ip)` | Lookup by IP string (`IpInfoQuery`) |
| `IpInfo::forRequest($request)` | Resolve client IP, then lookup |
| `IpInfo::forMany($ips)` | Batch lookup with cache-first + chain batch |
| `IpInfo::forManyQueued($ips)` | Dispatch `ProcessIpLookups` job |

`IpInfoQuery` helpers:

```php
IpInfo::for('8.8.8.8')->countryCode();
IpInfo::for('8.8.8.8')->geo();           // GeoLocation|null
IpInfo::for('8.8.8.8')->result();        // IpInfoResult
IpInfo::for('8.8.8.8')->providerTrace(); // per-provider ProviderResult[]
```

`IpInfoResult` / `GeoLocation` (v3.4+):

```php
$result = IpInfo::for('8.8.8.8')->result();
$result->forLogging();           // privacy-safe array
$result->toMinimalArray();
$result->isEu();
$result->isInContinent('EU');
$result->geo()?->countryNameOrCode();
```

Private, localhost, link-local, and reserved addresses are classified locally — external providers are not called for them.

## Security model (client IP)

- **Never trust `X-Forwarded-For` blindly.** Headers are read only when the remote address matches `trusted_proxies.proxy_cidrs`, unless `require_trusted_proxy_for_headers=false`.
- **Mirror Laravel `TrustProxies`** when using `respect_laravel=true` (default). Set `trusted_proxies.sync_with_laravel` as a reminder to keep both in sync.
- **Cloudflare presets** (`cloudflare`, `cloudflare_strict`) require `IP_INFO_TRUSTED_PROXY_CIDRS` (Cloudflare egress ranges) or network-level restriction. Presets do not embed rotating Cloudflare CIDRs.
- **`cloudflare_strict`** uses `CF-Connecting-IP` only (no `X-Forwarded-For`) to reduce header spoofing when proxy CIDRs are enforced.
- **HTTP providers** use HTTPS by default (`ipinfo` driver). Insecure `http://` URLs (e.g. `ip-api`) require `IP_INFO_HTTP_ALLOW_INSECURE=true`.
- **SSRF guard:** HTTP/CleanTalk URLs are validated against per-driver `allowed_hosts`.

Run `php artisan ip-info:sync` to surface missing proxy CIDRs, outdated published stubs, and unprotected opt-in routes.

## Middleware & validation

Publish middleware stubs:

```bash
php artisan vendor:publish --tag=ip-info-middleware
```

| Class | Role |
|-------|------|
| `ResolveClientIp` | Resolves client IP and attaches `ResolvedClientIpRequest` |
| `BlockCountries` | HTTP 403 when country is in block list |
| `AllowCountries` | HTTP 403 when country is not in allow list |

Lists come from middleware constructor args or `config('ip-info.security')`:

```env
IP_INFO_BLOCKED_COUNTRIES=RU,CN
IP_INFO_ALLOWED_COUNTRIES=US,CA,GB
```

Validation rules (import in form requests):

```php
use SuprunBohdan\IpInfo\Laravel\Http\Rules\ClientIpPublic;
use SuprunBohdan\IpInfo\Laravel\Http\Rules\CountryIn;

'ip' => ['required', new ClientIpPublic],
'country' => ['required', new CountryIn(['US', 'UA'])],
```

## Provider chain

Default chain: `local` → `database` → `maxmind` → `http` → `cleantalk`.

| Provider | When it runs |
|----------|----------------|
| `LocalProvider` | Private/local/reserved — no external call |
| `DatabaseRangeProvider` | Offline IPv4 ranges (optional DB) |
| `MaxMindProvider` | GeoLite2-Country MMDB (optional) |
| `HttpIpProvider` | `ip-api` or `ipinfo` HTTP drivers |
| `CleanTalkProvider` | Optional HTTP fallback |

Chain stops on provider `hit` or `miss`. Failures and skips continue to the next provider (`ProviderStatus`: `skipped`, `failed`, `miss`, `hit`).

**HTTP resilience (v3.2+):** retries, soft-fail on 429/5xx, optional circuit breaker per HTTP/CleanTalk config.

**Custom providers:**

```php
// config/ip-info.php
'providers' => [
    'custom' => [MyIpProvider::class],
],

// or listen to IpInfoBuildingChain event
```

## Batch & queue

```php
$results = IpInfo::forMany(['8.8.8.8', '1.1.1.1']);
// array<string, IpInfoResult>
```

`forMany()` resolves cache hits first, then `BatchIpProvider::lookupMany()` on the chain when available (offline DB uses one bounded SQL query). Remaining misses fall back to per-IP chain lookup.

```php
IpInfo::forManyQueued(['203.0.113.1', '203.0.113.2']);
// Dispatches ProcessIpLookups; listen for IpLookupsBatchCompleted
```

**Events:** `IpLookupStarted`, `IpLookupCompleted`, `IpLookupFailed`, `IpLookupsBatchCompleted`.

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

        $this->assertSame('UA', IpInfo::for('203.0.113.1')->countryCode());
    }
}
```

`IpInfo::fake()` and `fakeSequence()`:

- Replace the provider chain for the current manager instance.
- **Bypass positive and negative cache** — tests stay deterministic even when cache is populated.
- Do **not** write lookup results to production cache while fake mode is active.

```php
IpInfo::fake(['8.8.8.8' => 'UA']); // overrides cached US for 8.8.8.8
IpInfo::fakeSequence(['US', null, 'DE']); // rate-limit / fallback scenarios
IpInfo::assertLookedUp('8.8.8.8');
IpInfo::withCachePrefix('tenant-a'); // runtime cache namespace
```

Scaffold tests:

```bash
php artisan make:ip-info-test GeoMiddlewareTest
php artisan ip-info:publish-pest   # Pest helper stub
```

## Application sync

Audit how the package is integrated into your Laravel app:

```bash
php artisan ip-info:sync
php artisan ip-info:sync --json
php artisan ip-info:sync --fix
php artisan ip-info:sync --publish-config --publish-middleware
php artisan ip-info:sync --check-routes
php artisan ip-info:sync --force   # overwrite outdated stubs only (never user-modified)
```

| Flag | Behavior |
|------|----------|
| `--fix` | Publish missing stubs + run migrations (**safe only**) |
| `--publish-config` / `--publish-middleware` | Targeted publish when allowed |
| `--check-routes` | Routes health section only |
| `--force` | Overwrite **outdated** published files (not modified files) |

`--fix` does **not** edit `bootstrap/app.php`, `Kernel.php`, or `.env`. Preset recommendations are **report-only** — persist `IP_INFO_PRESET` and proxy CIDRs manually.

Published stubs include `@ip-info-stub-version` markers for version-aware drift detection.

When the HTTP route is enabled, protect it:

```env
IP_INFO_ROUTES_ENABLED=true
IP_INFO_ROUTE_PATH=/ip-info
IP_INFO_ROUTE_MIDDLEWARE=throttle:60,1
```

## Observability

| Integration | Enable | Notes |
|-------------|--------|-------|
| Laravel Pulse | `IP_INFO_PULSE_ENABLED=true` | `IpInfoRecorder` + optional Livewire card |
| Laravel Telescope | `IP_INFO_TELESCOPE_ENABLED=true` | `IpInfoTelescopeRecorder` (requires `laravel/telescope`) |

Privacy: inject `IpPrivacyPolicy` for logging decisions — DTOs do not read Laravel config directly.

## Configuration

File: `config/ip-info.php` (publish: `vendor:publish --tag=ip-info-config`)

| Section | Purpose |
|---------|---------|
| `cache` | Store, TTL, negative TTL, prefix, `tenant_prefix` |
| `lookup.request_memo` | In-request deduplication (default `true`) |
| `providers.chain` | Provider order; `providers.custom` for extensions |
| `presets` | `cloudflare`, `cloudflare_strict`, `nginx_proxy`, `local_only` |
| `database` | Offline IPv4 CSV |
| `maxmind` | GeoLite2-Country MMDB path and license |
| `http` | Driver, HTTPS enforcement, retries, circuit breaker |
| `cleantalk` | Optional HTTP fallback |
| `trusted_proxies` | Headers, proxy CIDRs, Laravel fallback |
| `security` | Default block/allow country lists |
| `privacy` | Logging, skip private IPs, redact headers |
| `routes` | Opt-in endpoint path and middleware |
| `pulse` / `telescope` | Optional observability toggles |

**Named preset** (applied when `IP_INFO_PRESET` is set at boot):

```env
IP_INFO_PRESET=cloudflare
```

## Artisan commands

| Command | Description |
|---------|-------------|
| `ip-info:install` | Publish config, migrate, optional `--preset`, `--with-database` |
| `ip-info:install-database` | Download IPv4 CSV and seed offline DB |
| `ip-info:update-database` | Refresh offline CSV database |
| `ip-info:update-maxmind` | Download GeoLite2-Country MMDB |
| `ip-info:sync` | Audit config, middleware, routes, security |
| `ip-info:sync --fix` | Safe publish/migrate only |
| `ip-info:diagnose` | Provider/runtime health + optional IP lookup |
| `ip-info:diagnose 8.8.8.8 --json` | Scriptable diagnostics |
| `ip-info:about` | Capability matrix (providers, features) |
| `ip-info:starter` | Publish starter middleware + config bundle |
| `ip-info:publish-schedule` | Weekly DB/MaxMind update schedule stubs |
| `ip-info:publish-pest` | Pest testing helper stub |
| `make:ip-info-test` | Scaffold feature test with `InteractsWithIpInfo` |

## Environment variables

| Variable | Purpose |
|----------|---------|
| `IP_INFO_CACHE_*` | Cache enable, store, TTL, prefix, tenant |
| `IP_INFO_DATABASE_ENABLED` | Offline IPv4 lookup |
| `IP_INFO_MAXMIND_*` | GeoLite2 MMDB |
| `IP_INFO_HTTP_*` | HTTP drivers, timeouts, circuit breaker |
| `IP_INFO_HTTP_ALLOW_INSECURE` | Allow `http://` drivers (e.g. ip-api) |
| `IP_INFO_CLEANTALK_*` | CleanTalk provider |
| `IP_INFO_TRUSTED_PROXY_CIDRS` | Required for header trust behind proxies |
| `IP_INFO_REQUIRE_TRUSTED_PROXY` | Enforce CIDR match (default `true`) |
| `IP_INFO_PRESET` | Named preset (`cloudflare`, etc.) |
| `IP_INFO_BLOCKED_COUNTRIES` / `IP_INFO_ALLOWED_COUNTRIES` | Geo middleware defaults |
| `IP_INFO_ROUTES_ENABLED` | Opt-in `/ip-info` route (default `false`) |
| `IP_INFO_ROUTE_MIDDLEWARE` | Comma-separated route middleware |
| `IP_INFO_PULSE_ENABLED` / `IP_INFO_TELESCOPE_ENABLED` | Observability |

See [docs/migration-guide.md](docs/migration-guide.md) for legacy `WTG_IP_INFO_*` → `IP_INFO_*` mapping.

## Architecture

| Layer | Role |
|-------|------|
| `IpInfoManager` | Orchestration, cache, events, fake mode, request memo |
| `IpProviderResolver` | Injectable provider access (no service locator) |
| `ChainProvider` | Ordered providers; `lookupMany()` for batch |
| `ProviderStatus` | Explicit `skipped` / `failed` / `miss` / `hit` |
| `IpCache` | Positive + negative TTL cache |
| `IpPrivacyPolicy` | Logging policy (not on DTOs) |
| `HealthChecker` | Shared checks for `diagnose` and `sync` |
| Custom providers | `providers.custom` or `IpInfoBuildingChain` event |

## What this package does

- Normalizes and validates IPv4/IPv6 input.
- Classifies public, private, localhost, link-local, and reserved addresses.
- Resolves client IP from HTTP requests using Laravel trusted proxy behavior by default.
- Looks up country codes through a configurable provider chain.
- Caches public IP lookups via Laravel cache stores (with negative cache).
- Optional HTTP endpoint, geo middleware, validation rules, queue batch jobs, Pulse/Telescope hooks.

**Non-goals in core:** full city/ASN databases, fraud scoring, PageRank-style ranking — see [docs/satellite-packages.md](docs/satellite-packages.md).

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
php artisan ip-info:update-database
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

Scheduled updates (publish stubs into your app):

```bash
php artisan ip-info:publish-schedule
```

## Documentation

| Doc | Topic |
|-----|-------|
| [CHANGELOG.md](CHANGELOG.md) | Release history (v3.2–v4.1) |
| [docs/migration-guide.md](docs/migration-guide.md) | Upgrades, sync, trusted proxy, HTTP HTTPS |
| [docs/vs-alternatives.md](docs/vs-alternatives.md) | Comparison with other Laravel geo packages |
| [docs/satellite-packages.md](docs/satellite-packages.md) | Planned ASN/fraud extensions |
| [docs/roadmap-v3.md](docs/roadmap-v3.md) | Roadmap |
| [docs/PACKAGIST.md](docs/PACKAGIST.md) | Release checklist |

## Development

```bash
composer test      # PHPUnit
composer analyse   # PHPStan
composer pint      # Code style
composer validate
```

Local Docker sandbox (gitignored):

```bash
cd sandbox && make init && make test
make test-package   # PHPUnit + PHPStan in package root
```

## License

MIT. See [LICENSE](LICENSE).
