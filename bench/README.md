# Benchmarks and stress tests

Micro-benchmarks for regression detection and optional HTTP stress under Docker.

## Quick run

```bash
composer install
composer bench
# or
make bench
```

Individual scripts:

```bash
php bench/lookup.php          # cached fake lookup path
php bench/batch.php           # forMany vs sequential
php bench/cache-scenarios.php # cache hit / miss / negative
php bench/location-db.php   # offline MMDB (downloads fixture once)
```

## Environment variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `BENCH_CACHE_HIT_ITERATIONS` | `5000` | Same-IP cache hit loop |
| `BENCH_CACHE_MISS_ITERATIONS` | `500` | Unique-IP miss loop |
| `BENCH_MMDB_WARM_ITERATIONS` | `5000` | MMDB warm loop |
| `BENCH_MMDB_COLD_ITERATIONS` | `500` | MMDB unique-IP loop |
| `BENCH_CACHE_HIT_MAX_MS` | `0.05` | Fail threshold per op |
| `BENCH_NEGATIVE_CACHE_MAX_MS` | `0.1` | Fail threshold per op |
| `BENCH_MMDB_WARM_MAX_MS` | `0.5` | Fail threshold per op |
| `BENCH_MMDB_COLD_MAX_MS` | `2.0` | Fail threshold per op |
| `SKIP_LOCATION_DB_BENCH` | — | Set `1` to skip MMDB download |

`location-db.php` requires `maxmind-db/reader` (included in dev dependencies).

Fixtures are stored under `bench/fixtures/` (gitignored).

## Docker stress (HTTP + wrk)

Full suite: micro-benchmarks + ephemeral Laravel app + `wrk` against `/bench/geo`:

```bash
make docker-stress
```

Scenarios:

1. **Cache hit** — `?ip=8.8.8.8` (same IP, warm cache)
2. **Cache miss** — varying `8.8.8.x` via `bench/wrk-miss.lua`

Requires network on first run to download MMDB for the stress app.
