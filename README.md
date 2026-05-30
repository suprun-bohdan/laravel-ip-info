# Laravel IP Info

Laravel package for IP detection, normalization, request IP resolution, geo lookup, caching, and infrastructure-aware IP intelligence.

Author: [Bohdan Suprun](mailto:bohdan-suprun@outlook.com)

## What this package does

- Normalizes and validates IPv4/IPv6 input.
- Classifies public, private, localhost, link-local, and reserved addresses.
- Resolves client IP from HTTP requests using Laravel trusted proxy behavior by default.
- Looks up country codes through a provider chain:
  - `LocalProvider` — private/local/reserved IPs (no external call)
  - `DatabaseRangeProvider` — optional offline IPv4 ranges
  - `CleanTalkProvider` — optional HTTP fallback
- Caches public IP lookups via Laravel cache stores.
- Optional HTTP endpoint and Artisan commands for install, update, and diagnostics.

## What this package does not do

- City/region/coordinates/ASN geo data.
- IPv6 offline range database (offline DB is IPv4-only).
- Blind trust of `X-Forwarded-For` or `CF-Connecting-IP` unless configured in `ip-info.trusted_proxies.headers`.
- Timezone-based country guessing.

## Requirements

- PHP ^8.2
- Laravel ^10, ^11, or ^12
- Laravel cache (array, file, redis, etc.)
- Database optional (only for offline IPv4 lookup)

## Installation

```bash
composer require suprun-bohdan/laravel-ip-info
```

Publish configuration:

```bash
php artisan vendor:publish --tag=ip-info-config
```

Optional offline database setup:

```bash
# enable in .env: IP_INFO_DATABASE_ENABLED=true
php artisan ip-info:install-database
```

Update the offline database later:

```bash
php artisan ip-info:update-database
php artisan ip-info:update-database --force
```

Diagnostics:

```bash
php artisan ip-info:diagnose
php artisan ip-info:diagnose 8.8.8.8
php artisan ip-info:diagnose 8.8.8.8 --json
```

JSON output is suitable for CI scripts and monitoring checks.

## Configuration

File: `config/ip-info.php`

| Section | Purpose |
|---------|---------|
| `cache` | Enable/disable cache, store, TTL, key prefix |
| `providers.chain` | Provider order: `local`, `database`, `cleantalk` |
| `providers.custom` | Additional container-resolvable `IpProvider` class names |
| `database.enabled` | Enable offline IPv4 table lookup |
| `cleantalk.enabled` | Enable CleanTalk HTTP provider |
| `routes.enabled` | Register bundled HTTP route (default: `false`) |
| `trusted_proxies` | Laravel proxy behavior and optional trusted headers |

Environment variables use the `IP_INFO_*` prefix (see config file).

## Basic usage

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$country = IpInfo::for('8.8.8.8')->countryCode();
$geo = IpInfo::for('8.8.8.8')->geo();
$isPublic = IpInfo::for('8.8.8.8')->isPublic();
$isPrivate = IpInfo::for('127.0.0.1')->isPrivate();
$payload = IpInfo::for('8.8.8.8')->result()->toArray();
```

## Request IP resolution

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$ip = IpInfo::forRequest($request)->ip();
$country = IpInfo::forRequest($request)->countryCode();
```

By default, only Laravel's `$request->ip()` is used. Custom headers are honored **only** when listed in `config('ip-info.trusted_proxies.headers')`.

Example for Cloudflare:

```php
// config/ip-info.php
'trusted_proxies' => [
    'respect_laravel' => true,
    'headers' => ['CF-Connecting-IP'],
],
```

Example for reverse proxy behind trusted infrastructure:

```php
'trusted_proxies' => [
    'respect_laravel' => true,
    'headers' => ['X-Forwarded-For', 'X-Real-IP'],
],
```

### Trusted proxy warning

Do not add `X-Forwarded-For`, `X-Real-IP`, or `CF-Connecting-IP` to trusted headers unless your infrastructure strips untrusted values before they reach PHP. Spoofed headers can otherwise replace the client IP.

## Provider lookup

Lookup order is configured in `providers.chain`.

- Private/local/reserved IPs stop at `LocalProvider` and return `null` country without external calls.
- Public IPv4 may match the offline `ip_country` table when `database.enabled=true`.
- CleanTalk is optional and disabled by default.

### Custom providers

Register additional providers in config:

```php
'providers' => [
    'custom' => [
        App\Geo\CustomIpProvider::class,
    ],
],
```

Or append providers when the `SuprunBohdan\IpInfo\Laravel\Events\IpInfoBuildingChain` event is dispatched during container registration.

## Cache behavior

- Enabled via `cache.enabled`.
- Uses Laravel cache stores (`cache.store`, `null` = default store).
- Keys: `{prefix}:v1:{ip}`.
- Private/local/reserved results are not cached.
- Failed lookups are not cached.

## HTTP endpoint (optional)

When `routes.enabled=true`:

- Path: `config('ip-info.routes.path')` (default `/ip-info`)
- Methods: GET, POST
- Returns JSON: `ip`, `country`, `is_public`, `is_private`, `provider`

## IPv4 / IPv6 support

| Feature | IPv4 | IPv6 |
|---------|------|------|
| Validation | yes | yes |
| Public/private classification | yes | yes |
| Offline DB lookup | yes | no |
| External provider (CleanTalk) | yes | yes |

## Testing

```bash
composer install
composer test
composer analyse
composer format:test
```

## Development (local Docker sandbox)

The repository includes a **local-only** Docker sandbox under `/sandbox/` (gitignored). It bootstraps Laravel 12 with a path repository to this package.

```bash
cd sandbox
make init          # Laravel app + composer path link
make start         # http://localhost:8088
make test          # pint, phpstan, phpunit + IP scenario matrix
make test-package
make test-scenarios
make shell
make stop
```

Sandbox files are not published with the Composer package.

## Migration

Upgrading from legacy `suprun-bohdan/laravel-ip-info` or pre-refactor APIs: see [docs/migration-guide.md](docs/migration-guide.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).
