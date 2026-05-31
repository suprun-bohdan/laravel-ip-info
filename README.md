# Laravel IP Info

[![Latest Version on Packagist](https://img.shields.io/packagist/v/suprun-bohdan/laravel-ip-info.svg?style=flat-square)](https://packagist.org/packages/suprun-bohdan/laravel-ip-info)
[![Tests](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml)
[![Benchmarks](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Laravel package for **smart IP resolution**, **geo lookup**, and **cache-first** IP intelligence — trusted proxies, provider chains, offline MMDB (IPv4 + IPv6), middleware, and testing fakes.

Author: [Bohdan Suprun](mailto:bohdan-suprun@outlook.com)

## Quick Start

```bash
composer require suprun-bohdan/laravel-ip-info
php artisan ip-info:install --quick --register-middleware --force
```

After install, public IP lookups work immediately via the HTTP provider (`quick_start` preset).

**For production**, prefer offline geo — no rate limits, no outbound HTTP:

```bash
composer require maxmind-db/reader
php artisan ip-info:install --with-location-db --preset=offline --force
```

See [Offline geo guide](docs/offline-geo.md) for country vs city editions and IPv6.

```php
// Helpers (v4.2+)
$country = client_country();
$country = client_country(default: 'XX');
$city = client_city(); // v4.6+, city edition or when MMDB has city data

// Fluent
if (ip_info()->isCountry('UA', 'PL')) {
    // ...
}
$city = ip_info()->city();           // v4.6+
$region = ip_info()->region();       // v4.6+
$tz = ip_info()->timezone();         // v4.6+
$coords = ip_info()->coordinates();  // v4.6+ ['lat' => ..., 'lon' => ...]

// Request macros
$country = request()->clientCountry();
$city = request()->clientCity();     // v4.6+
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

## IP risk and filtering (v4.5+)

Per-reason HTTP responses, risk scoring, block events, and verified crawler bypass:

```php
// Risk score (does not block by itself)
$risk = client_ip_risk();
$risk->score;   // 0–100
$risk->level;   // low | medium | high
$risk->signals; // [{reason, points}, ...]

request()->clientIpRisk()->isHigh();
is_verified_crawler(); // reverse DNS + forward confirm
```

Config (`config/ip-info.php`):

```php
'filtering' => [
    'responses' => [
        'tor' => ['status' => 451, 'message' => 'Tor connections are not allowed.'],
        'default' => ['status' => 403, 'message' => 'Access denied.'],
    ],
    'expose_block_reason_header' => true, // X-Ip-Info-Block-Reason
],
'risk' => [
    'weights' => ['tor' => 40, 'proxy' => 25, 'hosting' => 15, ...],
    'thresholds' => ['medium' => 30, 'high' => 60],
],
'verified_crawlers' => [
    'enabled' => true,
    'skip_filtering' => true, // Googlebot etc. bypass ip.filter
],
```

Listen for blocks:

```php
use SuprunBohdan\IpInfo\Laravel\Events\ClientIpBlocked;

Event::listen(ClientIpBlocked::class, function (ClientIpBlocked $event) {
    // $event->reason, $event->status, $event->intel
});
```

Blade: `@highrisk` … `@endhighrisk` wraps content when risk level is high.

## Blade (v4.4+)

### Directives

```blade
@country('UA', 'PL')
    Content for Ukraine or Poland
@endcountry

@unlesscountry('RU')
    Hidden for Russia
@endunlesscountry

@eu
    EU visitors only
@endeu

@continent('EU')
    European continent
@endcontinent

@privateip
    Local / RFC1918 client
@endprivateip

@publicip
    Public client IP
@endpublicip

@tor
    Tor exit detected
@endtor

@unlesstor
    Not Tor
@endunlesstor

@proxy
    Proxy or VPN detected
@endproxy

@unlessproxy
    Direct connection
@endunlessproxy

@anonymous
    Anonymous proxy signals
@endanonymous

@hosting
    Hosting / datacenter IP
@endhosting

> **v4.7+:** Cache v2 stores a JSON geo payload (country + whitelisted fields such as city/region/timezone/ASN). Set `IP_INFO_CACHE_STORE_GEO_FIELDS=false` to restore country-only v1 cache keys.

Country: @clientcountry('XX')
City: @clientcity('Unknown')
IP: @clientip
Anonymized: @anonymizedclientip
```

`@geoblock('RU')` aborts when the client matches (uses `ip-info.security.*` config). For middleware-level filtering use `ip.filter` instead of duplicating abort logic in Blade.

### Dev components (local / staging)

Publish CSS and optional view overrides:

```bash
php artisan vendor:publish --tag=ip-info-blade
# or during install:
php artisan ip-info:install --with-blade
```

In your layout:

```blade
@env('local')
    <link rel="stylesheet" href="{{ asset('vendor/ip-info/ip-info-blade.css') }}">
    <x-ip-info::dev-banner />
@endenv
```

| Component | Purpose |
|-----------|---------|
| `<x-ip-info::dev-banner />` | Country, continent, EU badge, anonymized IP, privacy/threat chips |
| `<x-ip-info::country-gate countries="UA,PL">` | Slot content when client country matches; optional `fallback` slot |
| `<x-ip-info::debug-panel />` | Collapsible `<details>` dump of geo, privacy, threats (no WHOIS) |

Props: `dev-banner` — `showIp`, `showThreats`, `onlyPrivate`; `debug-panel` — `collapsed` (default `true`).

Components use `ClientGeoData` and `ip_info()->threats()` only — no live WHOIS in views.

## Helpers reference (v4.2+)

| Function / macro | Returns | Notes |
|------------------|---------|-------|
| `ip_info(?string $ip = null)` | `IpInfoQuery` | Current request when `$ip` omitted |
| `client_country(?Request $r = null, ?string $default = null)` | `?string` | Uses request memo + cache |
| `client_city(?Request $r = null, ?string $default = null)` | `?string` | City from offline MMDB (v4.6+) |
| `client_asn(?Request $r = null, ?int $default = null)` | `?int` | ASN from offline enrichment (v4.7+) |
| `client_ip(?Request $r = null)` | `string` | Reuses middleware attribute when set |
| `client_ip_info(?Request $r = null)` | `IpInfoResult` | Full DTO |
| `normalize_ip(string $ip)` | `string` | Canonical IP (v4.3+) |
| `ip_privacy(?string $ip = null)` | `IpPrivacyProfile` | Private/public/reserved (v4.3+) |
| `ip_threats(?string $ip = null)` | `IpThreatSignals` | Tor/proxy/VPN/hosting (v4.3+) |
| `client_ip_intel(?Request $r = null, bool $withWhois = false)` | `ClientIpIntel` | Geo + privacy + threats + WHOIS (v4.3+) |
| `client_ip_risk(?Request $r = null)` | `ClientIpRiskScore` | Weighted risk score (v4.5+) |
| `is_verified_crawler(?string $ip = null)` | `bool` | Reverse DNS verified bot (v4.5+) |
| `whois_lookup(string $ip, bool $force = false)` | `?WhoisRecord` | Live WHOIS lookup (v4.3+) |
| `request()->clientCountry()` | `?string` | Macro |
| `request()->clientCity()` | `?string` | Macro (v4.6+) |
| `request()->ipInfo()` | `IpInfoResult` | Macro |
| `request()->clientIpRisk()` | `ClientIpRiskScore` | Macro (v4.5+) |
| `request()->isVerifiedCrawler()` | `bool` | Macro (v4.5+) |
| `request()->isCountry('UA', ...)` | `bool` | Macro |

Fluent on `IpInfoQuery` / `IpInfoResult`: `isCountry()`, `inCountries()`, `isEu()`, `countryOr()`, `countryOrFail()`, `city()`, `region()`, `timezone()`, `coordinates()` (v4.6+).

## Choosing a geo provider

The package tries providers in order (`providers.chain`). First hit wins.

| Provider | Data | IPv6 | HTTP | Best for |
|----------|------|------|------|----------|
| `local` | Built-in private/reserved rules | Yes | No | Always first — skips lookup for LAN IPs |
| **`location_db`** (v4.6+) | Country or city MMDB | **Yes** | No | **Recommended production offline** |
| `database` | Legacy CSV → `ip_country` table | No | No | Existing installs, IPv4 country only |
| `maxmind` | GeoLite2 MMDB | Yes | No | Teams with MaxMind license |
| `http` | ipinfo / ip-api | Yes | Yes | Dev / quick start |
| `cleantalk` | CleanTalk API | Varies | Yes | Optional enrichment |

**New projects:** use [`location_db`](docs/offline-geo.md) with `--preset=offline`.  
**Legacy projects:** keep `database` enabled; add `location_db` ahead of it in the chain for IPv6 and city.

```env
# Dev — instant geo via HTTP
IP_INFO_PRESET=quick_start

# Production — offline MMDB, no outbound geo HTTP
IP_INFO_PRESET=offline
IP_INFO_LOCATION_DB_ENABLED=true
IP_INFO_LOCATION_DB_EDITION=country
```

## Performance and cache-first design

Lookups are **cache-first**: request memo → Laravel cache (positive + negative TTL) → provider chain. Private IPs never hit external providers.

| Path | Typical latency |
|------|-----------------|
| Cache hit (same country code) | ~0.03 ms |
| Offline MMDB (`location_db`) | ~0.1 ms |
| HTTP provider (`quick_start`) | 50–500+ ms |

Measure in your environment:

```bash
composer bench       # cache hit/miss + MMDB micro-benchmarks
make docker-stress     # above + HTTP wrk stress (offline MMDB)
```

User guide: **[docs/benchmarks.md](docs/benchmarks.md)** · Maintainer options: [bench/README.md](bench/README.md)

> **v4.7+:** Cache v2 (`:v2:` keys) stores a JSON geo payload — city, region, timezone, and ASN survive cache hits when enabled via `IP_INFO_CACHE_STORE_GEO_FIELDS=true` (default). Fields respect the `location_db.fields` whitelist.

## Security model (client IP)

- **Never trust `X-Forwarded-For` blindly.** Headers are read only when the remote address matches `trusted_proxies.proxy_cidrs`, unless `require_trusted_proxy_for_headers=false`.
- **Mirror Laravel `TrustProxies`** when using `respect_laravel=true` (default).
- **Cloudflare presets** require `IP_INFO_TRUSTED_PROXY_CIDRS`. Refresh with `ip-info:refresh-cloudflare-cidrs`.
- **Runtime presets** — `IP_INFO_PRESET=cloudflare|nginx_proxy|offline|quick_start|local_only` (merged on each boot).
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

### Benchmarks and stress

```bash
composer bench       # micro-benchmarks: cache hit/miss, offline MMDB
make docker-stress   # bench + HTTP wrk on ephemeral Laravel app
```

Full guide: **[docs/benchmarks.md](docs/benchmarks.md)** · Script reference: [bench/README.md](bench/README.md)

### Docker verification

Run the full package suite and an ephemeral Laravel 11 app (path repo) inside Docker:

```bash
make docker-test    # package PHPUnit only
make docker-verify  # PHPUnit + Laravel integration
make docker-stress  # benchmarks + HTTP wrk stress (offline MMDB)
```

Micro-benchmarks without Docker:

```bash
composer bench
# or: make bench
```

No demo app is committed to the repository; `docker/verify.sh` and `docker/stress.sh` bootstrap a temporary app in a Docker volume.

## What this package does

- Normalizes and validates IPv4/IPv6 input.
- Classifies public, private, localhost, link-local, and reserved addresses.
- Resolves client IP from HTTP requests with trusted-proxy awareness.
- Looks up country codes through a provider chain (`local`, `location_db`, `database`, `maxmind`, `http`, `cleantalk`).
- Offline city, region, timezone, and coordinates via MMDB (`location_db`, v4.6+).
- Caches lookups with configurable positive and negative TTL (cache-first).
- Built-in benchmarks and Docker stress tests for cache, MMDB, and HTTP paths.
- Geo block/allow middleware, validation rules, sync/diagnose tooling.

## Requirements

- PHP ^8.2
- Laravel ^10, ^11, or ^12
- Laravel cache (array, file, redis, etc.)
- Database optional (legacy offline IPv4 CSV lookup)
- `maxmind-db/reader` optional (MaxMind GeoLite2 and ip-location-db MMDB)

## Installation options

```bash
composer require suprun-bohdan/laravel-ip-info

# Recommended for first run — HTTP geo works immediately
php artisan ip-info:install --quick

# Production presets
php artisan ip-info:install --preset=cloudflare
php artisan ip-info:install --preset=nginx_proxy
php artisan ip-info:install --with-database

# Offline geo without HTTP (IPv4 + IPv6, country or city)
php artisan ip-info:install --with-location-db --preset=offline --force
php artisan ip-info:install --with-location-db=city --preset=offline --force

# Optional automation
php artisan ip-info:install --register-middleware --force
php artisan ip-info:install --with-schedule
php artisan ip-info:starter --quick --register-middleware --force
```

Publish configuration only:

```bash
php artisan vendor:publish --tag=ip-info-config
```

### Offline geo without HTTP (v4.6+)

Full user guide: **[docs/offline-geo.md](docs/offline-geo.md)**

Download [DB-IP Lite](https://db-ip.com/) MMDB files via [sapics/ip-location-db](https://github.com/sapics/ip-location-db) (CC BY 4.0 — attribute db-ip.com when displaying geo data to users).

```bash
composer require maxmind-db/reader

# Country — IPv4 + IPv6, smallest download
php artisan ip-info:install --with-location-db --preset=offline --force

# City — city, region, postcode, lat/lon, timezone
php artisan ip-info:install --with-location-db=city --preset=offline --force

# Refresh / switch edition later
php artisan ip-info:update-location-db --force
php artisan ip-info:update-location-db --edition=city --force
```

```env
IP_INFO_PRESET=offline
IP_INFO_LOCATION_DB_ENABLED=true
IP_INFO_LOCATION_DB_EDITION=country
IP_INFO_LOCATION_DB_STALE_DAYS=30
```

```php
client_city();
ip_info('8.8.8.8')->city();
ip_info()->region();
ip_info()->timezone();
ip_info()->coordinates(); // ['lat' => float, 'lon' => float]|null
```

Limit returned fields in `config/ip-info.php` → `location_db.fields` (whitelist at lookup time).

**Legacy IPv4 CSV** (still supported, separate from MMDB):

```bash
# .env: IP_INFO_DATABASE_ENABLED=true
php artisan ip-info:install-database
php artisan ip-info:update-database
```

Optional MaxMind GeoLite2:

```bash
# .env: IP_INFO_MAXMIND_ENABLED=true, IP_INFO_MAXMIND_LICENSE_KEY=...
composer require maxmind-db/reader
php artisan ip-info:update-maxmind --edition=country
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
| `active_preset` / `IP_INFO_PRESET` | Runtime preset merge (`cloudflare`, `offline`, `quick_start`, …) |
| `cache` | TTL, negative cache, prefix, tenant prefix |
| `providers.chain` | Provider order |
| `presets` | Named proxy/provider bundles |
| `security` | Blocked/allowed countries, response status/message |
| `database` | Legacy offline IPv4 CSV (`ip_country` table) |
| `location_db` | Offline MMDB — edition, path, field whitelist (v4.6+) |
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
| `ip-info:install --with-location-db` | Download country MMDB (IPv4 + IPv6) |
| `ip-info:install --with-location-db=city` | Download city MMDB edition |
| `ip-info:install --with-location-db=asn_country` | Download ASN-country MMDB (CC0, v4.7+) |
| `ip-info:install --with-asn-db` | Download ASN enrichment MMDB (v4.7+) |
| `ip-info:install --preset=offline` | Chain without HTTP (`location_db`) |
| `ip-info:refresh-cloudflare-cidrs` | Fetch Cloudflare egress CIDRs for `.env` |
| `ip-info:sync` | Audit integration (config, middleware, routes, security) |
| `ip-info:diagnose` | Provider health + optional IP lookup |
| `ip-info:about` | Capability matrix (preset, helpers, aliases) |
| `ip-info:starter` | Publish middleware + install bundle |
| `ip-info:update-database` | Refresh offline IPv4 CSV |
| `ip-info:update-location-db` | Download ip-location-db MMDB files |
| `ip-info:update-maxmind` | Download GeoLite2 MMDB (`--edition=country\|city\|asn`) |
| `composer bench` | Run cache + MMDB micro-benchmarks |
| `make docker-stress` | Benchmarks + HTTP wrk stress (Docker) |

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

Public docs: **[docs/README.md](docs/README.md)**

| Guide | Description |
|-------|-------------|
| [Offline geo](docs/offline-geo.md) | MMDB install, `.env`, city API, updates, troubleshooting (v4.6+) |
| [Performance & benchmarks](docs/benchmarks.md) | Cache-first design, `composer bench`, `make docker-stress` |
| [Migration guide](docs/migration-guide.md) | Upgrades and breaking changes |
| [vs alternatives](docs/vs-alternatives.md) | Comparison with other Laravel geo packages |

## Development

```bash
composer test
composer bench
composer analyse
composer format:test
make docker-stress
```

Local Docker sandbox (gitignored): `cd sandbox && make init && make test`

## License

MIT. See [LICENSE](LICENSE).
