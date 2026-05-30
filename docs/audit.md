# Package Audit — Laravel IP Info

Audit date: baseline before refactor to `suprun-bohdan/laravel-ip-info` / `SuprunBohdan\IpInfo`.

## 1. Current package purpose

Country code lookup by IP address for Laravel applications. Combines:

- Optional offline IPv4 range database (CSV import + MySQL/SQLite table)
- CleanTalk HTTP API fallback
- Direct Redis caching via Predis
- Bundled HTTP endpoint and Artisan install command

Not a general IP intelligence or geo platform.

## 2. Current public API

| Surface | Entry | Notes |
|---------|-------|-------|
| HTTP | `GET/POST config('laravel-ip-info.route')` | `IPCheckController::checkIP()` returns array |
| Artisan | `laravel-ip-info:install` | Download CSV, migrate, seed |
| Facade | `IpInfo` | Declared in composer; **container binding missing** |
| DI | `IPCheckService::ipToCountry()` | Primary programmatic API in practice |

No documented stable contract; README added post-rebrand describes actual behavior.

## 3. Current composer.json

- **name:** `suprun-bohdan/laravel-ip-info` (target: `suprun-bohdan/laravel-ip-info`)
- **php:** `^8.0`
- **illuminate/support:** `^8.0|^9.0|^10.0`
- **predis/predis:** `^2.0` (hard require)
- **require-dev:** phpunit, mockery only — **no orchestra/testbench**
- **minimum-stability:** `dev`
- **scripts:** `test` only

## 4. Current namespace

`SuprunBohdan\LaravelIpInfo\` (target: `SuprunBohdan\IpInfo\`).

## 5. Current service provider structure

`LaravelIpInfoServiceProvider`:

- `register()`: `mergeConfigFrom` only — no bindings
- `boot()`: publish config, load migrations, register `InstallCommand`, **always** load routes

Violates planned split: bindings in `register()`, optional routes in `boot()`.

## 6. Current Laravel integration

- Auto-discovery via `extra.laravel.providers`
- Facade alias `IpInfo` without accessor binding
- Migrations auto-loaded
- Routes auto-loaded (not opt-in)
- No config for enabling/disabling features

## 7. Current config structure

File: `src/config/laravel-ip-info.php` → key `laravel-ip-info`

Keys:

- `route` — HTTP path
- `redis.host|port|database|password|prefix` — direct Redis, not Laravel cache

Uses `env()` inside config file (acceptable for Laravel config, but couples to Redis).

## 8. Current cache/Redis usage

`RedisCacheService` instantiates `Predis\Client` directly.

`IPCacheService` wraps Redis with keys `{prefix}::{ip}` and fixed 24h TTL (not configurable).

Package fails open without Redis at runtime when cache is accessed.

## 9. Current provider/lookup logic

`IPCheckService::ipToCountry()` order:

1. Redis cache
2. Eloquent range query on `ip_country` (IPv4 `ip2long` only)
3. `IpApiService` → CleanTalk HTTP
4. On exception: timezone → country fallback

`IpApiService::getCountry()` **bug:** returns `response()->json()` (JsonResponse) when country missing — type violation.

Dead code: `fetchFromIpApi()` never called.

`IpCountryServiceInterface` not bound in container.

## 10. Current request IP detection logic

Only in `IPCheckController`:

```php
$request->input('ip')
    ?? $request->header('CF-Connecting-IP')
    ?? $request->ip();
```

User-controlled `ip` input and Cloudflare header without trusted-proxy policy.

## 11. Current IPv4 support

- Validation via `filter_var` in `IPCheckService`
- Offline lookup via `ip2long` + DB ranges
- Private/reserved ranges not classified before external API call

## 12. Current IPv6 support

- Validated in `validateAndConvertIp`
- `findCountryByIp(int $ipLong)` — **IPv6 cannot use DB lookup**
- External API may receive IPv6 strings

## 13. Current private/local/reserved IP handling

Controller treats only `127.0.0.1` and `::1` specially (timezone fallback).

No RFC1918, link-local, or reserved range handling in service layer.

Private IPs may still hit CleanTalk API.

## 14. Current proxy header handling

No integration with Laravel `TrustProxies` / `Request::ip()` semantics beyond default `$request->ip()`.

`CF-Connecting-IP` accepted without trust configuration.

Spoofing risk if route is public.

## 15. Current exception/error handling

- Broad `catch (\Exception)` in controller and service
- Errors logged; controller returns `'Unknown'` country
- `CountryStatus` enum mixes domain states with HTTP status codes (200, 404, 204)
- `ErrorHandlerService` exists but is **unused**

## 16. Current test coverage

| File | Status |
|------|--------|
| `tests/Unit/IPCheckServiceTest.php` | Broken — missing `IpApiService` constructor arg |
| `tests/Feature/InstallCommandTest.php` | Depends on network CSV + long seeder sleeps |
| `tests/TestCase.php` | Uses Orchestra Testbench — **not in composer.json** |

No `phpunit.xml`. No CI. No static analysis.

## 17. Current documentation quality

- `README.md` added — honest about limitations
- No CHANGELOG, LICENSE file, or API reference
- No proxy security warning in original code (partially in README)

## 18. Current package naming problems

- Composer name changed mid-flight (`wtg/ipcountrydetector` → `suprun-bohdan/laravel-ip-info`)
- Target identity: `suprun-bohdan/laravel-ip-info`, namespace `SuprunBohdan\IpInfo`
- Config key `laravel-ip-info` vs planned `ip-info`
- Command `laravel-ip-info:install` vs planned `ip-info:install-database`
- Class prefix inconsistency (`IPCheckService`, `IPCacheService`)

## 19. Current security risks

1. Header spoofing (`ip` param, `CF-Connecting-IP`)
2. External API calls for potentially private IPs
3. No HTTP timeout on CleanTalk requests
4. User-supplied IP passed to external URL query string
5. HTTP route enabled by default in any consuming app
6. `IpApiService` type bug may cause unexpected responses

## 20. Current signs of AI-generated code or text

- `sleep()` in install command and seeder (2–5 seconds)
- Verbose seeder progress logging to console
- Unused abstractions (`ErrorHandlerInterface`, `UpdateIpCsvFile` job)
- Broken facade + broken tests shipped together
- Generic docblocks repeating signatures
- Ukrainian comment in controller amid English codebase
- `minimum-stability: dev` without justification

## 21. What should be deleted

- `ErrorHandlerService`, `ErrorHandlerInterface`
- `UpdateIpCsvFile` job (or replace with `ip-info:update-database` command)
- `fetchFromIpApi()` dead method
- `IpApiService` / `IPCheckService` / `IPCacheService` / `RedisCacheService` (after replacement)
- `IPCheckController` (replace with optional `IpInfoController`)
- `sleep()` calls in CLI/seeder
- Old namespace `SuprunBohdan\LaravelIpInfo\*`

## 22. What should be preserved

- Offline CSV source (`@ip-location-db/asn-country-ipv4.csv`)
- Migration + seeder concept for `ip_country` table
- Install workflow (download → migrate → seed) as **optional** feature
- CleanTalk as optional external provider (fix implementation)
- Cache-before-lookup pattern (via Laravel cache abstraction)
- Idea of HTTP endpoint (make opt-in)

## 23. What should be rewritten

- Entire service layer → contracts, providers, resolvers, value objects
- Service provider → bindings + feature flags
- Config → cache/providers/routes/trusted_proxies structure
- Tests → unit + Testbench feature tests
- Composer metadata, tooling, CI
- README, CHANGELOG, LICENSE

## 24. What should be postponed

- Additional geo providers (MaxMind, ip-api, etc.)
- IPv6 offline database
- SSRF hardening beyond fixed provider URLs in config
- Timezone-to-country fallback (remove — unreliable)
- Packagist publish automation
