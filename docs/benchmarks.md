# Performance and benchmarks

How **laravel-ip-info** delivers fast geo lookups and how to measure them in your environment.

## Design: cache-first lookup

Every public IP lookup follows the same order:

```
request memo → Laravel cache (positive) → Laravel cache (negative) → provider chain
```

| Layer | What it does | Typical cost |
|-------|----------------|--------------|
| **Request memo** | Reuses result within the same HTTP request (`client_country()` called twice) | ~0 ms |
| **Positive cache** | Stores geo payload (v4.7+) or country code (v1 legacy) after first hit | ~0.03–0.05 ms/op |
| **Negative cache** | Remembers “no country” misses (`IP_INFO_CACHE_NEGATIVE_TTL`, default 5 min) | ~0.05 ms/op |
| **`local` provider** | Skips external lookup for private/reserved IPs | ~0 ms |
| **`location_db` (MMDB)** | Offline DB-IP lookup, IPv4 + IPv6 | ~0.1 ms/op |
| **`database` / `maxmind`** | SQL or GeoLite2 MMDB | varies |
| **`http`** | Outbound API (dev / fallback) | 50–500+ ms |

This matches the package goals:

- **Smart IP resolution** — trusted proxies, normalization, IPv6, presets
- **GEO lookup** — provider chain with offline MMDB (v4.6+)
- **Clean & consistent** — one API (`ip_info()`, helpers, middleware)
- **Fast & cache-first** — cache and request memo before any disk/network I/O

### Cache note (v4.7)

From v4.7, positive cache entries use **v2 JSON keys** (`laravel_ip_info:v2:{ip}`) and preserve whitelisted geo fields (city, region, timezone, ASN) according to `location_db.fields`. Legacy v1 country-only entries still work as fallback.

Disable extended cache payload:

```env
IP_INFO_CACHE_STORE_GEO_FIELDS=false
```

### Cache note (v4.6, superseded by v4.7)

Earlier releases stored **country code only** in v1 cache keys.

## Run micro-benchmarks

From the package root (or your app with the package as path repo):

```bash
composer install
composer bench
```

Or:

```bash
make bench
```

This runs:

| Script | Scenario |
|--------|----------|
| `bench/lookup.php` | Cached fake provider path (regression guard) |
| `bench/batch.php` | `IpInfo::forMany()` vs sequential |
| `bench/cache-scenarios.php` | Cache hit, miss storm, no cache, negative cache |
| `bench/location-db.php` | Offline MMDB warm + unique IPs |

`location-db.php` downloads a country IPv4 MMDB fixture once into `bench/fixtures/` (gitignored). Requires `maxmind-db/reader` (dev dependency of the package).

Skip MMDB bench in CI or air-gapped environments:

```bash
SKIP_LOCATION_DB_BENCH=1 composer bench
```

Technical reference (env vars, thresholds): [bench/README.md](../bench/README.md).

## Run HTTP stress (Docker)

Simulates real Laravel HTTP requests with offline MMDB and `wrk`:

```bash
make docker-stress
```

Steps performed:

1. `composer bench` — all micro-benchmarks
2. Ephemeral Laravel 11 app with `location_db` + cache enabled
3. **`wrk` cache hit** — `GET /bench/geo?ip=8.8.8.8` (same IP, warm cache)
4. **`wrk` cache miss** — varying `8.8.8.x` via Lua script (MMDB + cache writes)

Reference numbers (Docker, `php artisan serve`, not production tuning):

| Scenario | Approx. throughput |
|----------|-------------------|
| Cache hit (same IP) | ~30–40 req/s |
| MMDB + cache miss (unique IPs) | ~4–8 req/s |
| Micro-bench cache hit | ~0.03 ms/op |
| Micro-bench MMDB warm | ~0.11 ms/op |

`artisan serve` is single-process — use **Octane**, **FrankenPHP**, or **php-fpm + nginx** for realistic production stress. Point `wrk` at your app after enabling offline geo.

## Tune for production

```env
IP_INFO_CACHE_ENABLED=true
IP_INFO_CACHE_STORE=redis
IP_INFO_CACHE_TTL=86400
IP_INFO_CACHE_NEGATIVE_TTL=300
IP_INFO_REQUEST_MEMO=true
IP_INFO_PRESET=offline
IP_INFO_LOCATION_DB_ENABLED=true
```

Tips:

- **Same visitors, same IP** — cache hit rate dominates; Redis recommended at scale.
- **Unique IP every request** — cache helps less; prefer `location_db` or MMDB over HTTP.
- **Batch jobs** — use `IpInfo::forMany($ips)` instead of loops.
- **Warmup** — first lookup after deploy pays MMDB open cost; subsequent lookups are faster.

## Verify correctness + performance

```bash
make docker-verify   # PHPUnit + integration
make docker-stress   # benchmarks + wrk
php artisan ip-info:diagnose 8.8.8.8
php artisan ip-info:sync --json
```

CI runs `composer bench` on every push to `main` (see GitHub Actions **Benchmarks** workflow).

### CI stress job (v4.7.6+)

The **Stress** workflow (`.github/workflows/stress.yml`) runs on push to `main` and via `workflow_dispatch`. It executes `make docker-stress` with a 20-minute timeout.

The job sets `continue-on-error: true` — a stress failure does **not** block merges. Use it as an optional regression signal for HTTP + MMDB throughput, not as a required gate.

## Related

- [Offline geo guide](offline-geo.md) — install MMDB for production
- [Main README](../README.md) — full package overview
- [bench/README.md](../bench/README.md) — maintainer bench options and thresholds
