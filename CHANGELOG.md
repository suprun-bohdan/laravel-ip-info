# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [4.7.2] - 2026-05-30

### Added

- `whereAsn()` alias on `IpInfoQuery` / `IpInfoResult` (same as `isAsn()`).
- Sync/diagnose reporting for ASN MMDB (`location_db.asn_installed`, `location_db.asn_stale`).

### Fixed

- PHPStan level 6 clean (CI static analysis).
- ASN enrichment now runs on cache hits (v1 and v2); enriched ASN is written back to cache v2.
- `HealthChecker` tracks missing/stale `asn` edition when `enrich_asn` is enabled.
- Install `--with-asn-db` env snippet clarifies offline chain vs enrichment-only setup.

### Changed

- Published stub version bumped to `4.7.2`.

## [4.7.0] - 2026-05-30

### Added

- **Cache v2:** JSON geo payload (`:v2:` cache keys) preserves city, region, timezone, and ASN fields on cache hits; v1 country-only entries remain supported.
- Config `cache.store_geo_fields` (default `true`).
- **ASN MMDB editions:** `asn_country` primary edition (RouteViews, CC0) and `asn` enrichment (CC BY 4.0) via `@ip-location-db`.
- `AsnMmdbEnricher`, `AsnMmdbRecordMapper`; config `location_db.enrich_asn`.
- `ip-info:install --with-location-db=asn_country` and `--with-asn-db`.
- `ip-info:update-location-db --edition=asn_country|asn`.
- Fluent API: `asn()`, `asnOrganization()`, `isAsn()`; helper `client_asn()`.
- Blade `@clientcity`, `@city` / `@endcity`, `@unlesscity` / `@endunlesscity`.
- Config `frontend.expose_city` for Inertia/SPA payloads via `ClientGeoData::forFrontend()`.
- CI workflow `.github/workflows/stress.yml` (`workflow_dispatch` + `main` push, `make docker-stress`).

### Changed

- `IpInfoSyncInspector` requires `ip-info:update-location-db` schedule stub when `location_db` is enabled.
- Published stub version bumped to `4.7.0`.
- `ClientGeoData` includes optional `city` and `region` fields.

### Fixed

- City/region/timezone no longer lost after cache hit when using offline location DB.

## [4.6.0] - 2026-05-30

### Added

- **Offline location DB (MMDB):** `location_db` provider via [sapics/ip-location-db](https://github.com/sapics/ip-location-db) (DB-IP Lite, CC BY 4.0).
- IPv4 + IPv6 country and city editions; config `ip-info.location_db` with field whitelist.
- `LocationDbCatalog`, `LocationDbDownloader`, `MmdbReaderPool`, `MmdbRecordMapper`, `LocationDbProvider`.
- `ip-info:update-location-db {--edition=country|city} {--force}` command.
- `ip-info:install --with-location-db` and `--with-location-db=city` flags; `offline` preset.
- Extended `GeoLocation` with city, region, postcode, coordinates, timezone.
- Fluent API: `city()`, `region()`, `timezone()`, `coordinates()` on `IpInfoQuery` / `IpInfoResult`.
- Helper `client_city()` and request macro `clientCity()`.
- Sync/diagnose health checks for location DB staleness and missing MMDB files.
- User guide: [docs/offline-geo.md](docs/offline-geo.md).

### Changed

- Default provider chain includes `location_db` after `local`.
- Published stub version bumped to `4.6.0`.
- Schedule stub includes monthly `ip-info:update-location-db`.
- README and docs index updated for offline geo workflows.
- Benchmark suite: `bench/cache-scenarios.php`, `bench/location-db.php`, `composer bench`, `make docker-stress` (cache, MMDB, HTTP wrk).

## [4.5.0] - 2026-05-30

### Added

- **Per-reason filter responses:** `filtering.responses.{reason}` and `responses.default` via `FilterBlockResponseResolver`.
- Optional `X-Ip-Info-Block-Reason` response header (`filtering.expose_block_reason_header`).
- **`ClientIpRiskScore`** and `ClientIpRiskScorer` with config weights/thresholds; helpers `client_ip_risk()`, macro `clientIpRisk()`, Blade `@highrisk`.
- **`ClientIpBlocked` event** dispatched before `ip.filter` abort.
- **Verified crawlers:** reverse DNS check (`VerifiedCrawlerInspector`, `ReverseDnsResolver`); `is_verified_crawler()` helper; verified bots skip `ip.filter` when `verified_crawlers.skip_filtering=true`.
- Docker verification: `make docker-verify` (package PHPUnit + ephemeral Laravel app).

### Changed

- Published stub version bumped to `4.5.0`.

## [4.4.0] - 2026-05-30

### Added

- **Blade directives (v4.4):** `@eu`, `@continent`, `@privateip`, `@publicip`, `@tor`, `@unlesstor`, `@proxy`, `@unlessproxy`, `@anonymous`, `@hosting`, `@clientip`, `@anonymizedclientip`.
- **Blade components:** `<x-ip-info::dev-banner />`, `<x-ip-info::country-gate />`, `<x-ip-info::debug-panel />` with publishable BEM CSS (`ip-info-blade` tag).
- `IpInfoQuery::isInContinent()` for `@continent` directive.
- `ip-info:install --with-blade` publishes CSS and component views.
- `AboutCommand` documents `vendor:publish --tag=ip-info-blade`.

### Changed

- Published stub version bumped to `4.4.0`.

## [4.3.0] - 2026-05-30

### Added

- **IP inspection:** `IpPrivacyProfile`, `IpThreatSignals`, `IpPrivacyInspector`, `IpThreatInspector`, `RequestProxyInspector`.
- Helpers: `normalize_ip()`, `ip_privacy()`, `ip_threats()`, `request_behind_trusted_proxy()`, `whois_lookup()`, `client_ip_intel()`.
- Query/result: `normalizedIp()`, `privacy()`, `threats()`, `isTor()`, `isProxy()`, `isVpn()`, `isHosting()`, `isAnonymous()`, `whois()`, `intel()`.
- Request macros: `normalizedClientIp()`, `clientIpPrivacy()`, `clientIpThreats()`, `isTorClient()`, `isProxyClient()`, `isBehindTrustedProxy()`, `clientIpIntel()`.
- Validation rules: `ClientIpNotTor`, `ClientIpNotProxy`, `ValidNormalizedIp`.
- Config: `threat_intel.*`, `logging.*`, `filtering.*`, `whois.*`, `http.enrich_threat_signals`.
- HTTP provider threat enrichment from ip-api (`proxy`, `hosting`) and ipinfo (`privacy.*`).
- **Live WHOIS:** `WhoisLookupService` (IANA referral → RIR, TCP :43), `WhoisRecord` DTO, `WhoisParser`, `ip-info:whois {ip}`.
- **Client intel:** `ClientIpIntel`, `ClientIpIntelBuilder`, `ClientIpLogger`.
- Middleware aliases: `ip.log` (`LogClientIp`), `ip.filter` (`FilterClientIp`).
- IPv6 integration test coverage (`Ipv6IntegrationTest`, `IpInspectionTest`).

### Changed

- `IpNormalizer` canonicalizes IPv6, strips zone IDs, fixes IPv6 anonymization to /48.
- `IpInfoResult` includes optional `threats` in `toArray()`; `ProviderResult` may carry `IpThreatSignals`.
- `IpLookupContract` extended with `normalizedIp()`, `privacyProfile()`, `threatSignals()`.
- Published stub version bumped to `4.3.0`.

### Removed

- `.cursor/docs/` removed from repository; all `.cursor/` paths gitignored.

## [4.2.0] - 2026-05-30

### Added

- Global helpers: `ip_info()`, `client_country()`, `client_ip()`, `client_ip_info()`.
- Request macros: `ipInfo()`, `clientCountry()`, `clientIp()`, `isCountry()`.
- Fluent query/result helpers: `isCountry()`, `inCountries()`, `isEu()`, `countryOr()`, `countryOrFail()`.
- Runtime preset via `IP_INFO_PRESET` / `active_preset` (`PresetConfigurator`).
- `quick_start` preset and `ip-info:install --quick`.
- `ip-info:install --register-middleware`, `--with-schedule`.
- `ip-info:refresh-cloudflare-cidrs` command and `CloudflareCidrFetcher`.
- Route middleware aliases: `ip.resolve`, `geo.block`, `geo.allow`, `geo.share`.
- Parameterized `geo.block:RU,BY` / `geo.allow:UA,PL` middleware.
- Blade directives: `@country`, `@unlesscountry`, `@clientcountry`, `@geoblock`.
- Validation rules: `CountryNotIn`, `ClientCountryIn`.
- `ClientGeoData` DTO, `ClientGeoResource`, `ShareClientGeo` middleware.
- `HasGeoFromIp` Eloquent trait.
- Sync flags: `--register-middleware`, `--with-schedule`; extended inspector hints.

### Changed

- `BlockCountries` / `AllowCountries` accept route parameters; configurable block response message/status.
- `ip-info:publish-schedule` delegates to shared `ScheduleStubPublisher` (includes Cloudflare CIDR refresh stub).
- `ip-info:about` shows active preset, helpers, and middleware aliases.
- Published stub version bumped to `4.2.0`.

## [4.1.0] - 2026-05-30

### Added

- `php artisan ip-info:sync` — application integration audit (`--json`, `--fix`, `--publish-config`, `--publish-middleware`, `--check-routes`, `--force`).
- `routes.middleware` config / `IP_INFO_ROUTE_MIDDLEWARE` for opt-in route protection.
- `@ip-info-stub-version` markers for version-aware published file comparison.
- Sync layer: `IpInfoSyncInspector`, `IpInfoSyncFixer`, `HealthChecker` (shared with diagnose).
- `CidrMatcher`, `IpProviderResolver`, `MutableIpProviderResolver`, `ProviderStatus`.
- `ChainProvider::lookupMany()` batch chain resolution.

### Changed

- **Refactor:** `IpInfoManager` injects `IpProviderResolver` instead of calling `app(IpProvider::class)`.
- **Refactor:** `ProviderResult` uses explicit `ProviderStatus` enum (`skipped`, `failed`, `miss`, `hit`). Deprecated `$resolved` preserved for BC.
- **Security:** Trusted headers require matching `trusted_proxies.proxy_cidrs` by default.
- **Security:** HTTP providers reject insecure `http://` URLs unless `http.allow_insecure=true`. Default driver is `ipinfo` (HTTPS).
- **Testing:** `IpInfo::fake()` bypasses positive/negative cache and skips cache writes.
- **DX:** `IpPrivacyPolicy` owns logging policy; `IpInfoResult::shouldLog()` deprecated.
- `ip-info:diagnose` delegates database/MaxMind stale checks to `HealthChecker` and links to `ip-info:sync`.
- `ip-info:starter` runs `ip-info:sync` after publish instead of hardcoded middleware instructions.

### Fixed

- `ip-info.pulse.enabled=false` disables Pulse recorder registration.
- `ip-info:install --preset` documents that runtime preset must be persisted manually.

## [4.0.0] - 2026-05-30

### Added

- `ProcessIpLookups` queue job and `IpInfo::forManyQueued()`.
- `IpLookupsBatchCompleted` event.
- Optional `IpInfoTelescopeRecorder` for Laravel Telescope.
- Pulse Livewire `IpInfoCard` component and view.
- Satellite MVP specs for ASN and fraud packages in [docs/satellite-packages.md](docs/satellite-packages.md).

## [3.4.0] - 2026-05-30

### Added

- `IpInfo::fakeSequence()`, `IpInfo::assertLookedUp()`, `IpInfo::withCachePrefix()`.
- `ip-info:about` capability matrix command.
- Request-scoped lookup memoization (`lookup.request_memo`).
- `GeoLocation::isEu()`, `isInContinent()`, `countryNameOrCode()`.
- `IpInfoResult::forLogging()`, `toMinimalArray()`, `isEu()`, `isInContinent()`.

## [3.3.0] - 2026-05-30

### Added

- Validation rules: `ClientIpPublic`, `CountryIn`.
- Middleware: `BlockCountries`, `AllowCountries`.
- Preset `cloudflare_strict` (CF-Connecting-IP only).
- `privacy.skip_private_ips`, `privacy.redact_headers`, `cache.tenant_prefix`.
- `security.blocked_countries` / `security.allowed_countries` config.
- `trusted_proxies.sync_with_laravel` documentation flag.

## [3.2.0] - 2026-05-30

### Added

- HTTP resilience: 429/5xx soft-fail, retries, circuit breaker (`HttpCircuitBreaker`).
- Optimized `DatabaseRangeProvider::lookupMany()` bounding-box batch query.
- `IpInfoManager::forMany()` cache-first batch resolution.
- MaxMind stale/readable checks in `ip-info:diagnose --json`.
- `ip-info:publish-schedule` command for weekly update stubs.
- `BatchIpProvider` contract.

## [3.1.0] - 2026-05-30

### Changed

- HTTP providers inject `IpHttpClient` instead of using `Http` facade in core.
- `LaravelCacheIpCache` injects `Illuminate\Contracts\Cache\Repository` instead of `Cache` facade.
- `DatabaseRangeProvider` injects `SchemaInspector` instead of `Schema` facade.
- `IpInfoManager` injects `Illuminate\Contracts\Events\Dispatcher` instead of `Event` facade.
- Added PSR-18 HTTP client adapter (`Psr18IpHttpClient`) and Laravel HTTP adapter.

### Added

- `Contracts/IpHttpClient`, `Contracts/SchemaInspector`.
- [.cursor/docs/psr-refactor-plan.md](.cursor/docs/psr-refactor-plan.md).
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
- Updated [.cursor/docs/post-refactor-audit.md](.cursor/docs/post-refactor-audit.md) with v1.2/v1.3 status.

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
- [.cursor/docs/post-refactor-audit.md](.cursor/docs/post-refactor-audit.md).

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
