# Offline geo without HTTP

Guide for end users who want country (or city) lookup **without** calling external HTTP APIs — including **IPv6**.

## When to use this

| Goal | Recommended approach |
|------|----------------------|
| Try the package in 30 seconds | `php artisan ip-info:install --quick` (HTTP) |
| Production, country only, no extra deps beyond MMDB reader | **`location_db` country edition** (v4.6+) |
| Production, need city / region / timezone offline | **`location_db` city edition** (v4.6+) |
| Legacy install with MySQL/SQLite `ip_country` table | `database` provider (IPv4 country only) |
| You already have MaxMind license + GeoLite2 | `maxmind` provider |

The **`location_db`** provider downloads ready-made [DB-IP Lite](https://db-ip.com/) MMDB files from [sapics/ip-location-db](https://github.com/sapics/ip-location-db). Lookups are local, fast, and work for both IPv4 and IPv6.

> **License:** DB-IP Lite data is [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/). When you show geo data to users, attribute [db-ip.com](https://db-ip.com/) (e.g. footer link or “Geo data by DB-IP”).

## Quick install

### Country edition (smaller files)

```bash
composer require suprun-bohdan/laravel-ip-info
composer require maxmind-db/reader

php artisan ip-info:install --with-location-db --preset=offline --force
php artisan ip-info:install --register-middleware --force
```

### City edition (city, state, lat/lon, timezone)

```bash
php artisan ip-info:install --with-location-db=city --preset=offline --force
```

The install command downloads IPv4 + IPv6 `.mmdb` files and prints suggested `.env` lines.

## `.env` reference

```env
IP_INFO_PRESET=offline
IP_INFO_LOCATION_DB_ENABLED=true
IP_INFO_LOCATION_DB_EDITION=country
# IP_INFO_LOCATION_DB_EDITION=city

IP_INFO_LOCATION_DB_PATH=/full/path/to/storage/app/ip-info/location-db
IP_INFO_LOCATION_DB_STALE_DAYS=30
IP_INFO_LOCATION_DB_SOURCE=dbip
```

With `offline` preset, HTTP and legacy CSV providers stay disabled. The provider chain becomes roughly: `local` → `location_db` → `null`.

## Usage in PHP

```php
// Country (same as before)
$country = client_country();
$country = ip_info('8.8.8.8')->countryCode();

// City / region / timezone (city edition or country edition where available)
$city = client_city();
$city = ip_info()->city();
$city = request()->clientCity();

$region = ip_info()->region();       // state / province (state1)
$tz = ip_info()->timezone();
$coords = ip_info()->coordinates(); // ['lat' => float, 'lon' => float] or null

// Full DTO
$result = client_ip_info();
$result->geo?->city;
$result->geo?->latitude;
```

`client_ip_info()` and `IpInfoResult::toArray()` include the new geo fields when present.

## Limit which fields are returned

Edit `config/ip-info.php`:

```php
'location_db' => [
    'fields' => ['country', 'city', 'region'], // drop lat/lon if you do not need them
],
```

The whitelist applies at **lookup mapping** time, not when downloading MMDB files.

## ASN editions (v4.7+)

Two optional RouteViews-based MMDB sets from [sapics/ip-location-db](https://github.com/sapics/ip-location-db):

| Edition | Package | License | Use |
|---------|---------|---------|-----|
| `asn_country` | `@ip-location-db/asn-country-mmdb` | CC0 | Primary **country** lookup without DB-IP |
| `asn` | `@ip-location-db/asn-mmdb` | CC BY 4.0 | **Enrichment** — ASN number + organization after geo hit |

### Install ASN-country as primary edition

```bash
php artisan ip-info:install --with-location-db=asn_country --preset=offline --force
```

### Install ASN enrichment (alongside country/city)

```bash
php artisan ip-info:install --with-asn-db --force
```

```env
IP_INFO_LOCATION_DB_ENRICH_ASN=true
IP_INFO_LOCATION_DB_SOURCE=routeviews
```

Usage:

```php
$asn = ip_info()->asn();
$org = ip_info()->asnOrganization();
$asn = client_asn();
```

> **License:** ASN MMDB (`asn` edition) is CC BY 4.0 — attribute RouteViews / ip-location-db when displaying ASN data.

## Cache v2 (v4.7+)

Extended geo fields survive cache hits when `IP_INFO_CACHE_STORE_GEO_FIELDS=true` (default). Payload respects `location_db.fields`. Legacy v1 cache entries (country string only) remain supported.

## Keeping data fresh

```bash
# Re-download current edition
php artisan ip-info:update-location-db --force

# Switch edition
php artisan ip-info:update-location-db --edition=city --force
php artisan ip-info:update-location-db --edition=asn_country --force
php artisan ip-info:update-location-db --edition=asn --force
```

Optional schedule (append during install or sync):

```bash
php artisan ip-info:install --with-schedule
# or: php artisan ip-info:sync --with-schedule
```

Stub added to `routes/console.php`:

```php
Schedule::monthly()->command('ip-info:update-location-db');
```

Check health:

```bash
php artisan ip-info:diagnose
php artisan ip-info:sync --json
```

If MMDB files are missing or older than `stale_days`, sync/diagnose suggest running `ip-info:update-location-db --force`.

## Files on disk

Default storage:

```
storage/app/ip-info/location-db/
├── country-ipv4.mmdb   # or city-ipv4.mmdb
├── country-ipv6.mmdb   # or city-ipv6.mmdb
└── metadata.json
```

Ensure the directory is writable by the web/queue user and included in backups if geo is business-critical.

## MaxMind GeoLite2 editions (optional)

If you have a [MaxMind license key](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data), use the `maxmind` provider instead of CDN `location_db` files:

| Edition | Env | Command |
|---------|-----|---------|
| Country | `IP_INFO_MAXMIND_EDITION=country` | `ip-info:update-maxmind --edition=country` |
| City | `IP_INFO_MAXMIND_EDITION=city` | `ip-info:update-maxmind --edition=city` |
| ASN | `IP_INFO_MAXMIND_EDITION=asn` | `ip-info:update-maxmind --edition=asn` |

When `IP_INFO_MAXMIND_DATABASE_PATH` is unset, files default to `storage/app/private/geoip/GeoLite2-{Edition}.mmdb` (local disk).

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Country always `null`, no errors | Run `composer require maxmind-db/reader` — required to read `.mmdb` files |
| IPv6 always `null`, IPv4 works | Run `ip-info:update-location-db --force` — both ipv4 and ipv6 files must exist |
| `location_db` skipped in diagnose | Set `IP_INFO_LOCATION_DB_ENABLED=true` or `IP_INFO_PRESET=offline` |
| Stale warning in `ip-info:sync` | `php artisan ip-info:update-location-db --force` |
| City fields always `null` | Use `--with-location-db=city` or `--edition=city`, not country edition |
| Private IP returns `null` | Expected — private/reserved addresses skip external lookup |

Test a specific IP:

```bash
php artisan ip-info:diagnose 8.8.8.8
php artisan ip-info:diagnose 2001:4860:4860::8888
```

## Legacy `ip_country` CSV (still supported)

Older installs use SQL + CSV (`ip-info:update-database`). That path is **IPv4 country only**.

You can run **both** legacy CSV and `location_db` in the provider chain; `location_db` is tried first when enabled. New projects should prefer `location_db` for IPv6 and city data.

## Related

- [Main README](../README.md) — full package overview
- [Performance & benchmarks](benchmarks.md) — cache-first design and stress tests
- [Migration guide](migration-guide.md) — upgrading to 4.6+
- [vs alternatives](vs-alternatives.md) — comparison with other packages
