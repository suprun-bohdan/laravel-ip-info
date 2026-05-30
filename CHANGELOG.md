# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [3.1.0] - 2026-05-30

### Changed

- HTTP providers inject `IpHttpClient` instead of using `Http` facade in core.
- `LaravelCacheIpCache` injects `Illuminate\Contracts\Cache\Repository` instead of `Cache` facade.
- `DatabaseRangeProvider` injects `SchemaInspector` instead of `Schema` facade.
- `IpInfoManager` injects `Illuminate\Contracts\Events\Dispatcher` instead of `Event` facade.
- Added PSR-18 HTTP client adapter (`Psr18IpHttpClient`) and Laravel HTTP adapter.

### Added

- `Contracts/IpHttpClient`, `Contracts/SchemaInspector`.
- [docs/psr-refactor-plan.md](docs/psr-refactor-plan.md).
- Composer requirements: `psr/http-client`, `psr/http-factory`, `psr/http-message`.

## [3.0.0] - 2026-05-30

### Added

- Optional Laravel Pulse `IpInfoRecorder` (lookup counts, cache hits, top countries).
- `ip-info:starter` command publishing middleware + config bundle.
- [docs/vs-alternatives.md](docs/vs-alternatives.md) and [docs/satellite-packages.md](docs/satellite-packages.md).

## [2.3.0] - 2026-05-30

### Added

- Extended `GeoLocation` fields: `countryName`, `continent`, `isEu`.
- `HttpIpProvider` with `ip-api` and `ipinfo` drivers.
- Shared `UrlAllowlistGuard` for HTTP provider SSRF protection.
- `IpInfoResult::shouldLog()` and `IpInfoResult::anonymized()` privacy helpers.

## [2.2.0] - 2026-05-30

### Added

- `MaxMindProvider` and `ip-info:update-maxmind` command.
- Negative cache (`cache.negative_ttl`) for unresolved public lookups.
- `IpInfo::forMany()` batch helper.
- Lookup events: `IpLookupStarted`, `IpLookupCompleted`, `IpLookupFailed`.
- Benchmark workflow (`.github/workflows/bench.yml`).

## [2.1.0] - 2026-05-30

### Added

- `IpInfo::fake()` and `InteractsWithIpInfo` testing trait.
- `ResolveClientIp` middleware and `ResolvedClientIpRequest` form request.
- Config presets: `cloudflare`, `nginx_proxy`, `local_only`.
- `ip-info:install` command with `--preset` and `--with-database`.
- Stale offline DB detection in `ip-info:diagnose --json` (non-zero exit when stale).
- Packagist publish checklist ([docs/PACKAGIST.md](docs/PACKAGIST.md)) and README Quick Start with CI badges.

## [2.0.0] - 2026-05-30

### Changed

- **Breaking:** Composer package renamed from `wtg/laravel-ip-info` to `suprun-bohdan/laravel-ip-info`.
- **Breaking:** Namespace renamed from `Wtg\IpInfo` to `SuprunBohdan\IpInfo`.
- **Breaking:** Environment variables renamed from `WTG_IP_INFO_*` to `IP_INFO_*`.
- **Breaking:** Default cache key prefix changed from `wtg_ip_info` to `laravel_ip_info`.

### Removed

- All WTG branding from package identity, diagnostics output, and documentation.

## [1.3.0] - 2026-05-30

### Added

- `IpInfoResult::toArray()` and `JsonSerializable` support.
- `ip-info:diagnose --json` for scriptable diagnostics.
- [docs/migration-guide.md](docs/migration-guide.md).
- README development section for local Docker sandbox workflow.

### Changed

- CleanTalk provider enforces `api.cleantalk.org` host allowlist and disables HTTP redirects (SSRF hardening).
- Updated [docs/post-refactor-audit.md](docs/post-refactor-audit.md) with v1.2/v1.3 status.

### Postponed

- IPv6 offline database and additional HTTP geo providers (planned for future release).

## [1.2.0] - 2026-05-30

### Added

- RFC 5737 TEST-NET ranges (`192.0.2.0/24`, `198.51.100.0/24`, `203.0.113.0/24`) treated as reserved.
- IPv6 unspecified (`::`) and multicast (`ff00::/8`) skip external lookup.
- `IpLookupContract` interface with container alias for lookup orchestration.
- Config file section comments for all keys.
- Seeder progress output and `IpRange::ipv4ToLong()` usage in CSV import.
- Tests: trusted proxy headers (CF-Connecting-IP, IPv4-mapped), database boundaries, custom provider chain, cache TTL, CleanTalk HTTP/SSRF failures, diagnose JSON, `IpInfoResult` serialization.

### Changed

- `IpInfoQuery` depends on `IpLookupContract` instead of concrete manager.
- Documented `ProviderResult` resolved vs countryCode semantics.
- Clarified `ChainProvider` aggregate naming when no provider resolves.

## [1.1.1] - 2026-05-30

### Fixed

- Moved `IpCountrySeeder` to PSR-4 path `src/Laravel/Database/Seeders/`.
- `DiagnoseIpCommand` uses `countryCode()` method on result DTO.
- `CsvFilePathService::putCsvFile()` returns file path string.
- PHPStan: `Application` type hint in service provider, config excluded from Larastan env checks, 512M memory limit.
- PHPUnit: `RefreshDatabase` in cache tests; memoization test uses stub provider instead of mocking final `IpInfoManager`.
- Pint formatting across package source and tests.

### Changed

- `.gitignore` excludes local `/sandbox/` Docker development tree.

## [1.1.0] - 2026-05-30

### Added

- `IpInfoQuery` lookup memoization (single provider/cache pass per query object).
- `GeoLocation` value object on `IpInfoResult` with `countryCode()` helper.
- `ip-info:update-database` command with optional `--force` CSV re-download.
- Provider extension via `providers.custom` config and `IpInfoBuildingChain` event.
- Larastan static analysis configuration.
- Tests: cache behavior, route gating, install/update commands, CleanTalk provider, chain exception fallback, `IpRange`, query memoization.
- Laravel 12 support in CI matrix.
- [docs/post-refactor-audit.md](docs/post-refactor-audit.md).

### Changed

- `IpValidator` consolidates private/local/reserved classification in `shouldSkipExternalLookup()`.
- `DatabaseRangeProvider` caches table existence check after first lookup.
- `LocalProvider` and `IpInfoManager` delegate to consolidated validator logic.

## [1.0.0] - 2026-05-30

### Added

- Package identity `suprun-bohdan/laravel-ip-info` with namespace `SuprunBohdan\IpInfo`.
- `IpInfo` facade and `IpInfoManager` public API.
- IP normalization, validation, and classification (`IpNormalizer`, `IpValidator`).
- Provider chain: `LocalProvider`, `DatabaseRangeProvider`, `CleanTalkProvider`, `NullProvider`.
- Laravel cache-backed `IpCache` implementation.
- `RequestIpResolver` with configurable trusted headers.
- Artisan commands: `ip-info:install-database`, `ip-info:diagnose`.
- Optional HTTP route (disabled by default).
- PHPUnit tests, Pint, PHPStan, GitHub Actions CI.
- Documentation: audit, refactor plan, composer review.

### Changed

- **Breaking:** Replaced legacy `IPCheckService` and always-on HTTP route.
- **Breaking:** Config key/file changed from `laravel-ip-info` to `ip-info`.
- **Breaking:** Removed direct Predis dependency; uses Laravel cache.
- **Breaking:** Removed timezone-to-country fallback.

### Removed

- Legacy `SuprunBohdan\LaravelIpInfo` namespace and dead code.

[2.0.0]: https://github.com/suprun-bohdan/laravel-ip-info/releases/tag/v2.0.0
[1.3.0]: https://github.com/suprun-bohdan/laravel-ip-info/releases/tag/v1.3.0
[1.2.0]: https://github.com/suprun-bohdan/laravel-ip-info/releases/tag/v1.2.0
[1.1.1]: https://github.com/suprun-bohdan/laravel-ip-info/releases/tag/v1.1.1
[1.1.0]: https://github.com/suprun-bohdan/laravel-ip-info/releases/tag/v1.1.0
[1.0.0]: https://github.com/suprun-bohdan/laravel-ip-info/releases/tag/v1.0.0
