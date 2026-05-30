# PSR & Laravel Package Standards — Audit & Refactor Plan

Audit date: 2026-05-30  
Package: `suprun-bohdan/laravel-ip-info`  
Namespace: `SuprunBohdan\IpInfo`

## Executive summary

The package already meets several professional standards (PSR-4, Pint/PSR-12, Testbench, package discovery, contracts for lookup/cache/providers). The main gaps are **Laravel facades inside reusable provider/cache code** and missing **explicit HTTP/cache abstractions** wired via constructor injection.

This plan applies the **smallest safe refactor** from the maintainer prompt — no full rewrite, no unnecessary PSR packages.

---

## Phase 0 — Audit findings

### composer.json ✅ mostly OK

| Item | Status |
|------|--------|
| PSR-4 `SuprunBohdan\IpInfo\` → `src/` | OK |
| PSR-4 dev `Tests\` → `tests/` | OK |
| `extra.laravel.providers` + aliases | OK |
| Scripts: `test`, `format`, `analyse`, `validate` | OK |
| `laravel/pint` in require-dev | OK |
| Missing PSR HTTP interfaces | **Fix** — HTTP providers exist |
| Missing `psr/log` | **Defer** — logging only in Artisan/Seeder layer |

### src/ structure

```
src/
├── Cache/           ← LaravelCacheIpCache used Cache facade (fix)
├── Contracts/       ← IpProvider, IpCache, IpLookupContract, IpResolver
├── Data/            ← DTOs (GeoLocation, IpInfoResult, …)
├── Exceptions/
├── Http/            ← NEW: PSR-18 client adapter
├── Laravel/         ← ServiceProvider, commands, middleware, Pulse
├── Providers/       ← HTTP providers used Http facade (fix)
├── Resolvers/
├── Support/
└── Testing/
```

No rename of `Data/` → `DTO/` — avoid BC noise; `Data/` is already clear.

### Facade usage map

| Location | Facade | Verdict |
|----------|--------|---------|
| `CleanTalkProvider`, `HttpIpProvider` | `Http` | **Fix** — inject `IpHttpClient` |
| `LaravelCacheIpCache` | `Cache` | **Fix** — inject `Cache\Repository` |
| `DatabaseRangeProvider` | `Schema` | **Fix** — inject `SchemaInspector` |
| `IpInfoManager`, `IpInfoServiceProvider` | `Event` | **Fix** — inject `Events\Dispatcher` |
| Artisan commands, seeders | `Log`, `Http`, `Artisan`, `Storage` | **Keep** — Laravel integration layer |
| `RequestIpResolver`, providers | `config()` | **Keep** — acceptable Laravel ergonomics |

### Tests ✅

- Orchestra Testbench — OK
- 45 PHPUnit tests — OK
- Missing: explicit `IpHttpClient` / `SchemaInspector` binding tests — **Add**

---

## Phase 1 — Required PSR (implement now)

### PSR-4 ✅

No change.

### PSR-12 ✅

Keep `pint.json` preset `laravel`. Run `composer format` in CI.

### PSR-18 / PSR-7 / PSR-17 (HTTP)

**Justification:** `CleanTalkProvider`, `HttpIpProvider` perform outbound HTTP.

**Actions:**

1. Add to `require`:
   - `psr/http-client`
   - `psr/http-message`
   - `psr/http-factory`
2. Add `Contracts/IpHttpClient.php` — package HTTP port (returns status + JSON).
3. Add `Http/Psr18IpHttpClient.php` — PSR-18 + PSR-17 implementation.
4. Add `Laravel/Http/LaravelIpHttpClient.php` — wraps injected `Illuminate\Http\Client\Factory` (keeps `Http::fake()` in tests).
5. Register `IpHttpClient` in `IpInfoServiceProvider` (Laravel adapter by default).
6. Inject into HTTP providers; remove `Http` facade from `Providers/`.

### Cache (PSR-16 partial)

**Justification:** Package caches lookup results with negative-cache semantics.

**Decision:** Keep domain `IpCache` contract (negative cache is not PSR-16). Replace `Cache` facade with injected `Illuminate\Contracts\Cache\Repository` in `LaravelCacheIpCache`.

**Not adding** `psr/simple-cache` as a hard dependency — custom `IpCache` is the public abstraction; Laravel adapter uses Repository.

---

## Phase 2 — Intentionally skipped PSR

| Standard | Decision | Reason |
|----------|----------|--------|
| PSR-3 | Not added now | `Log` only in commands/seeders (Laravel layer), not core lookup logic |
| PSR-6 | Skip | Overkill; domain `IpCache` + Laravel Repository sufficient |
| PSR-11 | Skip | Laravel container + constructor injection enough |
| PSR-13 / 14 / 15 / 20 | Skip | No link relations, events bus, middleware pipeline, clock abstraction needed |

---

## Phase 3 — Architecture rules (ongoing)

1. **Core providers/resolvers** — no Laravel facades; inject contracts.
2. **Laravel namespace** — ServiceProvider wiring, commands, middleware, Pulse, HTTP/cache adapters.
3. **Public API** — no breaking changes to `IpInfo` facade, `IpInfoManager`, config keys.
4. **BC risk** — `CleanTalkProvider` / `HttpIpProvider` constructors gain optional HTTP client only if resolved via container (manual `new` in tests must pass client).

---

## Phase 4 — Test plan

| Test | Purpose |
|------|---------|
| `IpHttpClientBindingTest` | Container resolves `IpHttpClient` |
| Update `CleanTalkProviderTest` | Resolve provider from container after `Http::fake()` |
| Update `HttpIpProviderTest` | Same |
| Existing cache tests | Must pass with Repository injection |

---

## Phase 5 — Verification commands

```bash
composer validate --strict
composer dump-autoload -o
composer test
composer analyse
composer format:test
php bench/lookup.php
```

---

## Remaining TODOs (future, optional)

- [ ] Inject `Psr\Log\LoggerInterface` into Artisan commands (PSR-3) if commands are extracted to reusable services.
- [ ] `Psr16IpCache` adapter if standalone (non-Laravel) usage becomes a goal.
- [ ] Bind `ClientInterface` + `RequestFactoryInterface` in apps that prefer pure PSR-18 over Laravel HTTP factory.
- [ ] Move `config()` reads in providers to injected `Repository` for stricter core purity.

---

## Expected report template (post-implementation)

### 1. What was changed

- Introduced `IpHttpClient` contract + `Psr18IpHttpClient` (PSR-18) + `LaravelIpHttpClient` adapter.
- `CleanTalkProvider` / `HttpIpProvider` inject `IpHttpClient` (no `Http` facade in providers).
- `LaravelCacheIpCache` injects `Illuminate\Contracts\Cache\Repository` (no `Cache` facade).
- `DatabaseRangeProvider` injects `SchemaInspector` (no `Schema` facade in provider).
- `IpInfoManager` injects `Events\Dispatcher` (no `Event` facade).
- `IpInfoServiceProvider` wires all bindings; uses `Dispatcher` for chain event.

### 2. PSR standards now supported

| PSR | Status |
|-----|--------|
| PSR-4 | ✅ Already present |
| PSR-12 | ✅ Via Laravel Pint |
| PSR-7/17/18 | ✅ Interfaces in require; `Psr18IpHttpClient` + Laravel adapter |
| PSR-3 | ⏸ Deferred (logging only in Artisan layer) |
| PSR-16 | ⏸ Partial — domain `IpCache` + Laravel `Repository` adapter |

### 3. PSR intentionally not added

See Phase 2 table above.

### 4. Files modified/added

**Added:** `Contracts/IpHttpClient.php`, `Contracts/SchemaInspector.php`, `Http/IpHttpResponse.php`, `Http/Psr18IpHttpClient.php`, `Laravel/Http/LaravelIpHttpClient.php`, `Laravel/Database/LaravelSchemaInspector.php`, `tests/Feature/IpHttpClientBindingTest.php`, `docs/psr-refactor-plan.md`

**Modified:** `Providers/CleanTalkProvider.php`, `Providers/HttpIpProvider.php`, `Providers/DatabaseRangeProvider.php`, `Cache/LaravelCacheIpCache.php`, `Laravel/IpInfoManager.php`, `Laravel/IpInfoServiceProvider.php`, `composer.json`, `CHANGELOG.md`, related tests

### 5. Tests

- 47 tests, 93 assertions — all green
- Added `IpHttpClientBindingTest`
- Updated provider tests to resolve from container

### 6. Commands executed

```bash
composer update psr/http-client psr/http-factory psr/http-message
vendor/bin/phpunit          # OK
vendor/bin/phpstan analyse  # OK
vendor/bin/pint             # OK
composer validate --strict  # OK
```

### 7. Backward compatibility risks

- **Low:** Manual `new CleanTalkProvider($validator)` breaks — must pass `IpHttpClient` or use container.
- **Low:** Manual `new DatabaseRangeProvider($validator)` breaks — must pass `SchemaInspector`.
- Public facade/config/API unchanged.

### 8. Remaining TODOs

See Phase 5 optional items (PSR-3 in commands, PSR-16 standalone adapter, config Repository injection in providers).
