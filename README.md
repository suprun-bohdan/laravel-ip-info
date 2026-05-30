# Laravel IP Info

[![Latest Version on Packagist](https://img.shields.io/packagist/v/suprun-bohdan/laravel-ip-info.svg?style=flat-square)](https://packagist.org/packages/suprun-bohdan/laravel-ip-info)
[![Tests](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/tests.yml)
[![Benchmarks](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml/badge.svg)](https://github.com/suprun-bohdan/laravel-ip-info/actions/workflows/bench.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Laravel package for IP detection, normalization, request IP resolution, geo lookup, caching, and infrastructure-aware IP intelligence.

Author: [Bohdan Suprun](mailto:bohdan-suprun@outlook.com)

## Quick Start

```bash
composer require suprun-bohdan/laravel-ip-info
php artisan ip-info:install
```

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$country = IpInfo::for('8.8.8.8')->countryCode(); // "US"
$client = IpInfo::forRequest(request())->countryCode();
```

Testing:

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Testing\InteractsWithIpInfo;

class ExampleTest extends TestCase
{
    use InteractsWithIpInfo;

    public function test_country(): void
    {
        $this->fakeIpInfo(['203.0.113.1' => 'UA']);

        $this->assertSame('UA', IpInfo::for('203.0.113.1')->countryCode());
    }
}
```

Middleware (Cloudflare preset):

```bash
php artisan vendor:publish --tag=ip-info-middleware
# .env: IP_INFO_PRESET=cloudflare
```

## What this package does

- Normalizes and validates IPv4/IPv6 input.
- Classifies public, private, localhost, link-local, and reserved addresses.
- Resolves client IP from HTTP requests using Laravel trusted proxy behavior by default.
- Looks up country codes through a provider chain:
  - `LocalProvider` — private/local/reserved IPs (no external call)
  - `DatabaseRangeProvider` — optional offline IPv4 ranges
  - `MaxMindProvider` — optional offline GeoLite2 MMDB
  - `HttpIpProvider` — optional HTTP drivers (`ip-api`, `ipinfo`)
  - `CleanTalkProvider` — optional HTTP fallback
- Caches public IP lookups via Laravel cache stores (with negative cache).
- Optional HTTP endpoint, middleware, and Artisan commands.

## Requirements

- PHP ^8.2
- Laravel ^10, ^11, or ^12
- Laravel cache (array, file, redis, etc.)
- Database optional (offline IPv4 lookup)
- `maxmind-db/reader` optional (MaxMind MMDB lookup)

## Installation

```bash
composer require suprun-bohdan/laravel-ip-info
php artisan ip-info:install
php artisan ip-info:install --preset=cloudflare
php artisan ip-info:install --with-database
```

Publish configuration only:

```bash
php artisan vendor:publish --tag=ip-info-config
```

Optional offline database:

```bash
# .env: IP_INFO_DATABASE_ENABLED=true
php artisan ip-info:install-database
```

Optional MaxMind GeoLite2:

```bash
# .env: IP_INFO_MAXMIND_ENABLED=true, IP_INFO_MAXMIND_LICENSE_KEY=...
composer require maxmind-db/reader
php artisan ip-info:update-maxmind
```

Diagnostics:

```bash
php artisan ip-info:diagnose
php artisan ip-info:diagnose 8.8.8.8 --json
```

## Configuration

File: `config/ip-info.php`

| Section | Purpose |
|---------|---------|
| `cache` | Enable/disable cache, store, TTL, negative TTL, key prefix |
| `providers.chain` | Provider order: `local`, `database`, `maxmind`, `http`, `cleantalk` |
| `presets` | Named proxy/provider presets (`cloudflare`, `nginx_proxy`, `local_only`) |
| `maxmind` | GeoLite2 MMDB path and license key |
| `http` | HTTP driver selection and timeouts |
| `privacy` | Logging and anonymization helpers |

See [docs/migration-guide.md](docs/migration-guide.md) and [docs/roadmap-v3.md](docs/roadmap-v3.md).

## Commands

| Command | Description |
|---------|-------------|
| `ip-info:install` | Publish config, migrate, optional preset/database |
| `ip-info:install-database` | Download IPv4 CSV and seed offline DB |
| `ip-info:update-database` | Refresh offline CSV database |
| `ip-info:update-maxmind` | Download GeoLite2-Country MMDB |
| `ip-info:diagnose` | Configuration and lookup diagnostics |
| `ip-info:starter` | Publish starter middleware/config bundle |

## Development

Local Docker sandbox (gitignored):

```bash
cd sandbox && make init && make test
make test-package   # PHPUnit + PHPStan in package root
```

## License

MIT. See [LICENSE](LICENSE).
