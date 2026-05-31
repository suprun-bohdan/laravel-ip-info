# Migration Guide — Laravel IP Info

Guide for upgrading from legacy package versions or pre-refactor installs.

## Package identity

| Legacy / interim | Current (v2.0+) |
|------------------|-----------------|
| `wtg/laravel-ip-info` | `suprun-bohdan/laravel-ip-info` |
| `Wtg\IpInfo` | `SuprunBohdan\IpInfo` |
| `SuprunBohdan\LaravelIpInfo` (pre-refactor) | `SuprunBohdan\IpInfo` |
| `config/laravel-ip-info.php` | `config/ip-info.php` |
| `IPCheckService` facade/service | `IpInfo` facade + `IpInfoManager` |

## Config key migration

Replace published config and `.env` keys:

| Legacy / old | New |
|--------------|-----|
| `WTG_IP_INFO_*` | `IP_INFO_*` |
| `LARAVEL_IP_INFO_*` | `IP_INFO_*` |
| `wtg_ip_info` cache prefix | `laravel_ip_info` |
| `laravel-ip-info.php` | `ip-info.php` |

Run:

```bash
php artisan vendor:publish --tag=ip-info-config --force
```

Review `config/ip-info.php` and copy values from the old file manually.

## API migration

### Before (legacy)

```php
// Removed — do not use
$country = app('IPCheckService')->getCountry($ip);
```

### After (v1.x)

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$country = IpInfo::for($ip)->countryCode();
$requestIp = IpInfo::forRequest($request)->ip();
$result = IpInfo::for($ip)->result(); // IpInfoResult DTO
```

## Breaking changes since v1.0

1. HTTP route is **opt-in** (`IP_INFO_ROUTES_ENABLED=false` by default).
2. No timezone-to-country fallback.
3. No direct Redis/Predis dependency — uses Laravel cache.
4. Trusted proxy headers are **not** read unless listed in `ip-info.trusted_proxies.headers`.
5. Legacy offline CSV (`database` provider) is IPv4-only — use `location_db` (v4.6+) for IPv6 and city fields.

### Upgrading to 2.0

1. Update `composer.json`: `composer require suprun-bohdan/laravel-ip-info:^2.0`
2. Replace all `use Wtg\IpInfo\...` imports with `use SuprunBohdan\IpInfo\...`
3. Rename `.env` keys from `WTG_IP_INFO_*` to `IP_INFO_*`
4. Clear config cache and republish config if needed

## Upgrading 1.x → 1.2

### 1.1

- `IpInfoResult::countryCode()` method (not property).
- `ip-info:update-database` command added.
- Provider extension via `providers.custom` and `IpInfoBuildingChain` event.

### 1.2

- RFC 5737 TEST-NET ranges treated as reserved (skip external lookup).
- IPv6 `::` and multicast (`ff00::/8`) skip external lookup.
- `IpInfoResult::toArray()` / `JsonSerializable`.
- `ip-info:diagnose --json`.
- CleanTalk URL host allowlist (`api.cleantalk.org` only).
- `IpLookupContract` for extension/testing.

### 1.3

- Documentation and DX improvements (README, migration guide, dev sandbox notes).
- IPv6 offline geo via MMDB (`location_db` provider, v4.6+); legacy `ip_country` CSV remains IPv4-only.

## Seeder namespace (1.1.1 fix)

If you referenced the seeder class directly, update imports:

```php
use SuprunBohdan\IpInfo\Laravel\Database\Seeders\IpCountrySeeder;
```

PSR-4 path: `src/Laravel/Database/Seeders/IpCountrySeeder.php`.

## Upgrading to 4.7

No breaking API changes. Recommended steps:

1. `composer update suprun-bohdan/laravel-ip-info`
2. Republish config for new keys: `php artisan vendor:publish --tag=ip-info-config`
3. Optional — enable cache v2 geo fields (default `true`):

```env
IP_INFO_CACHE_STORE_GEO_FIELDS=true
```

4. Optional — ASN MMDB:

```bash
php artisan ip-info:install --with-asn-db --force
# or primary asn_country edition:
php artisan ip-info:install --with-location-db=asn_country --preset=offline --force
```

5. Optional — expose city in Inertia/SPA payloads:

```env
IP_INFO_FRONTEND_EXPOSE_CITY=true
```

New `.env` keys:

| Key | Purpose |
|-----|---------|
| `IP_INFO_CACHE_STORE_GEO_FIELDS` | Cache v2 JSON geo payload (default `true`) |
| `IP_INFO_LOCATION_DB_ENRICH_ASN` | Post-lookup ASN enrichment |
| `IP_INFO_LOCATION_DB_EDITION=asn_country` | RouteViews country MMDB as primary |
| `IP_INFO_FRONTEND_EXPOSE_CITY` | Include city/region in `ClientGeoData::forFrontend()` |
| `IP_INFO_FRONTEND_EXPOSE_COORDINATES` | Include lat/lon in `ClientGeoData::forFrontend()` (v4.7.5+) |

Existing v1 cache entries continue to work; they upgrade to v2 on the next cache miss.

## Upgrading to 4.7.8

Patch release — no breaking changes. Fixes CI (Pint + PHP 8.2 test matrix).

1. `composer update suprun-bohdan/laravel-ip-info`

## Upgrading to 4.7.5

Patch release — no breaking changes.

1. `composer update suprun-bohdan/laravel-ip-info`
2. Optional — set `IP_INFO_FRONTEND_EXPOSE_COORDINATES=true` to include `lat`/`lon` in Inertia/SPA payloads via `ClientGeoData::forFrontend()`.

## Upgrading to 4.7.4

Patch release — no breaking changes.

1. `composer update suprun-bohdan/laravel-ip-info`
2. Optional — run `ip-info:update-location-db` once to populate ETag metadata for conditional CDN downloads.

## Upgrading to 4.7.3

Patch release — no breaking changes.

1. `composer update suprun-bohdan/laravel-ip-info`
2. Optional — choose MaxMind edition:

| Edition | Env | Download |
|---------|-----|----------|
| Country (default) | `IP_INFO_MAXMIND_EDITION=country` | `ip-info:update-maxmind --edition=country` |
| City | `IP_INFO_MAXMIND_EDITION=city` | `ip-info:update-maxmind --edition=city` |
| ASN | `IP_INFO_MAXMIND_EDITION=asn` | `ip-info:update-maxmind --edition=asn` |

When `IP_INFO_MAXMIND_DATABASE_PATH` is unset, the MMDB path defaults to `storage/app/private/geoip/GeoLite2-{Edition}.mmdb` for the configured edition.

## Upgrading to 4.7.2

Patch release — no breaking changes.

1. `composer update suprun-bohdan/laravel-ip-info`
2. If you use `enrich_asn`, run `ip-info:sync --json` — missing/stale ASN MMDB is now reported.
3. ASN enrichment runs on cache hits (v1/v2); no action required unless you rely on stale v1-only entries without ASN.

## Upgrading to 4.6

New optional offline provider **`location_db`** — no breaking changes for existing installs.

1. `composer update suprun-bohdan/laravel-ip-info`
2. Republish config if you want new keys: `php artisan vendor:publish --tag=ip-info-config`
3. Optional — enable MMDB geo:

```bash
composer require maxmind-db/reader
php artisan ip-info:install --with-location-db --preset=offline --force
```

Step-by-step user guide: **[offline-geo.md](offline-geo.md)**

New `.env` keys (all optional):

| Key | Purpose |
|-----|---------|
| `IP_INFO_LOCATION_DB_ENABLED` | Turn on MMDB provider |
| `IP_INFO_LOCATION_DB_EDITION` | `country` or `city` |
| `IP_INFO_LOCATION_DB_PATH` | Storage directory for `.mmdb` files |
| `IP_INFO_LOCATION_DB_STALE_DAYS` | Warn in sync/diagnose when files are old |
| `IP_INFO_PRESET=offline` | Disable HTTP; chain uses `location_db` |

New helpers: `client_city()`, `request()->clientCity()`, `ip_info()->city()`, `region()`, `timezone()`, `coordinates()`.

## v2.1+ features

| Feature | Usage |
|---------|-------|
| Testing fake | `IpInfo::fake(['8.8.8.8' => 'US'])` |
| Middleware | `ResolveClientIp` + `--tag=ip-info-middleware` |
| Install wizard | `php artisan ip-info:install --preset=cloudflare` |
| MaxMind | `IP_INFO_MAXMIND_ENABLED=true` + `ip-info:update-maxmind` |
| HTTP drivers | `IP_INFO_HTTP_ENABLED=true`, `IP_INFO_HTTP_DRIVER=ip-api` |
| Batch lookup | `IpInfo::forMany(['8.8.8.8', '1.1.1.1'])` |

## Upgrading to 3.2

No breaking changes. New optional config keys:

| Key | Default | Purpose |
|-----|---------|---------|
| `http.retries` | `1` | Idempotent GET retry count |
| `http.circuit_breaker.enabled` | `false` | Open circuit after HTTP failures |
| `http.circuit_breaker.failure_threshold` | `5` | Failures before circuit opens |
| `http.circuit_breaker.ttl` | `60` | Seconds circuit stays open |

HTTP providers soft-fail on 429/5xx (chain continues). Run `ip-info:publish-schedule` for weekly DB update stubs.

`ip-info:diagnose --json` adds `maxmind.stale`, `maxmind.readable`, `http.circuit_open`.

## Upgrading to 3.3

Opt-in security artifacts — publish middleware or import rules directly:

```bash
php artisan vendor:publish --tag=ip-info-middleware
```

| Feature | Usage |
|---------|-------|
| `ClientIpPublic` rule | Reject private/reserved IPs in forms |
| `CountryIn` rule | Geo-gate validated fields |
| `BlockCountries` / `AllowCountries` | Route middleware |
| `cloudflare_strict` preset | CF-Connecting-IP only (anti-spoof) |
| `privacy.skip_private_ips` | Skip events for private IPs |
| `cache.tenant_prefix` | Multi-tenant cache isolation |

Set `trusted_proxies.sync_with_laravel=true` and mirror values in Laravel `TrustProxies` middleware.

## Upgrading to 3.4

| Feature | Usage |
|---------|-------|
| `IpInfo::fakeSequence()` | Queue responses for rate-limit tests |
| `IpInfo::assertLookedUp('8.8.8.8')` | Assert fake was called |
| `IpInfo::withCachePrefix('tenant-1')` | Runtime cache namespace |
| `lookup.request_memo` | In-request dedup (default `true`) |
| `ip-info:about` | Capability matrix |
| `make:ip-info-test` | Scaffold feature test with fake |
| Pest helper | `vendor:publish --tag=ip-info-pest` |

Result helpers: `forLogging()`, `toMinimalArray()`, `isEu()`, `isInContinent()`, `countryNameOrCode()`.

## Upgrading to 4.0

| Feature | Usage |
|---------|-------|
| `IpInfo::forManyQueued($ips)` | Dispatch `ProcessIpLookups` job |
| `IpLookupsBatchCompleted` | Event after queued batch |
| Telescope | Optional `IpInfoTelescopeRecorder` (requires `laravel/telescope`) |
| Pulse card | Livewire `IpInfoCard` when Pulse + Livewire installed |

## Refactoring notes (post v4.0)

| Change | Action required |
|--------|-----------------|
| Trusted headers | Set `IP_INFO_TRUSTED_PROXY_CIDRS` or disable `IP_INFO_REQUIRE_TRUSTED_PROXY=false` |
| HTTP `ip-api` driver | Set `IP_INFO_HTTP_ALLOW_INSECURE=true` or switch to `ipinfo` |
| `ProviderResult::$resolved` | Prefer `$result->status` / `isHit()` / `shouldStopChain()` |
| `IpInfoResult::shouldLog()` | Inject `IpPrivacyPolicy` |
| Fake in tests | No manual cache flush needed — fake bypasses cache |

## Application sync (4.1+)

```bash
php artisan ip-info:sync
php artisan ip-info:sync --json
php artisan ip-info:sync --fix
```

| Flag | Behavior |
|------|----------|
| `--fix` | Publish missing stubs + migrate (safe only) |
| `--publish-config` / `--publish-middleware` | Targeted publish |
| `--force` | Overwrite **outdated** stubs only (never modified files) |
| `--check-routes` | Routes health section only |

Route protection when enabling the endpoint:

```env
IP_INFO_ROUTES_ENABLED=true
IP_INFO_ROUTE_MIDDLEWARE=throttle:60,1
```

Preset recommendations from sync are **report-only** — persist `.env` / `config/ip-info.php` manually.

## DX helpers (4.2+)

Global helpers (autoloaded):

```php
client_country();                    // ?string
client_country(default: 'XX');      // fallback
client_ip();
client_ip_info();                    // IpInfoResult
ip_info('8.8.8.8')->countryOr('XX');
```

Request macros:

```php
request()->clientCountry();
request()->isCountry('UA', 'PL');
request()->ipInfo();
```

Route middleware aliases:

```php
Route::middleware(['ip.resolve', 'geo.block:RU,BY'])->group(function () {
    // ...
});
```

Runtime preset — set once in `.env`:

```env
IP_INFO_PRESET=cloudflare
```

Install shortcuts:

```bash
php artisan ip-info:install --quick
php artisan ip-info:install --register-middleware --force
php artisan ip-info:install --with-blade
php artisan ip-info:refresh-cloudflare-cidrs --write-env-snippet
php artisan vendor:publish --tag=ip-info-blade
php artisan ip-info:sync --register-middleware --force
```

## Blade DX (4.4+)

Publish dev UI assets (optional):

```bash
php artisan vendor:publish --tag=ip-info-blade
```

```blade
@env('local')
    <link rel="stylesheet" href="{{ asset('vendor/ip-info/ip-info-blade.css') }}">
    <x-ip-info::dev-banner />
@endenv

@tor
    Tor exit detected
@endtor

@highrisk
    Elevated IP risk (v4.5+)
@endhighrisk
```

Directives: `@eu`, `@continent`, `@privateip`, `@publicip`, `@tor`, `@proxy`, `@clientip`, `@anonymizedclientip`, and matching `@end*` tags. Components: `dev-banner`, `country-gate`, `debug-panel`.

## IP risk and filtering (4.5+)

```php
$risk = client_ip_risk(); // score 0–100, level low|medium|high
is_verified_crawler();    // reverse DNS verified bot

Event::listen(ClientIpBlocked::class, function (ClientIpBlocked $event) {
    // $event->reason, $event->status, $event->intel
});
```

Config highlights:

```php
'filtering' => [
    'responses' => [
        'tor' => ['status' => 451, 'message' => 'Tor not allowed'],
    ],
    'expose_block_reason_header' => true,
],
'risk' => ['weights' => [...], 'thresholds' => ['medium' => 30, 'high' => 60]],
'verified_crawlers' => ['enabled' => true, 'skip_filtering' => true],
```

## Offline location DB (4.6+)

See the full **[offline geo user guide](offline-geo.md)** for install, troubleshooting, and attribution requirements.

IPv4 + IPv6 geo without HTTP using [sapics/ip-location-db](https://github.com/sapics/ip-location-db) MMDB files (DB-IP Lite, [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/) — attribute [db-ip.com](https://db-ip.com/) when displaying geo data).

```bash
composer require maxmind-db/reader

# Country edition
php artisan ip-info:install --with-location-db --preset=offline --force

# City edition (city, region, lat/lon, timezone)
php artisan ip-info:install --with-location-db=city --preset=offline --force

php artisan ip-info:update-location-db --force
```

`.env`:

```env
IP_INFO_PRESET=offline
IP_INFO_LOCATION_DB_ENABLED=true
IP_INFO_LOCATION_DB_EDITION=country
IP_INFO_LOCATION_DB_STALE_DAYS=30
```

Config highlights:

```php
'location_db' => [
    'enabled' => true,
    'edition' => 'country', // or city
    'fields' => ['country', 'city', 'region', 'postcode', 'latitude', 'longitude', 'timezone'],
],
'providers' => [
    'chain' => ['local', 'location_db', 'database', 'maxmind', 'http', 'cleantalk'],
],
```

API:

```php
ip_info($ip)->city();
ip_info($ip)->region();
ip_info($ip)->timezone();
ip_info($ip)->coordinates();
client_city();
$request->clientCity();
```

Legacy `ip_country` SQL seed and `@country` Blade directives are unchanged. Use `location_db` for IPv6 and city-level fields.

## Verification checklist

```bash
composer test
composer bench
composer analyse
make docker-verify   # PHPUnit + ephemeral Laravel app in Docker
make docker-stress   # cache/MMDB benchmarks + HTTP wrk stress
php artisan ip-info:diagnose --json
php artisan ip-info:diagnose 8.8.8.8
```

See [benchmarks.md](benchmarks.md) for interpreting bench output.
