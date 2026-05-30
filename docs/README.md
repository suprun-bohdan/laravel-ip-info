# Documentation

User documentation for **[suprun-bohdan/laravel-ip-info](https://github.com/suprun-bohdan/laravel-ip-info)**.

Start with the [main README](../README.md) for installation, middleware, Blade, and helpers.

## Guides

| Document | Who it is for |
|----------|----------------|
| [offline-geo.md](offline-geo.md) | **Production apps** that need country or city lookup without HTTP (IPv4 + IPv6, v4.6+) |
| [migration-guide.md](migration-guide.md) | Upgrading from older package versions; version-by-version changes |
| [vs-alternatives.md](vs-alternatives.md) | Choosing between this package and torann/geoip, stevebauman/location, etc. |

## Common tasks

### First run (HTTP geo, good for dev)

```bash
composer require suprun-bohdan/laravel-ip-info
php artisan ip-info:install --quick --register-middleware --force
```

### Production offline (no HTTP, IPv4 + IPv6)

See **[offline-geo.md](offline-geo.md)** — summary:

```bash
composer require maxmind-db/reader
php artisan ip-info:install --with-location-db --preset=offline --force
php artisan ip-info:install --register-middleware --force
```

### Behind Cloudflare

```bash
php artisan ip-info:install --preset=cloudflare
php artisan ip-info:refresh-cloudflare-cidrs --write-env-snippet
```

### Audit your app integration

```bash
php artisan ip-info:sync
php artisan ip-info:sync --json
php artisan ip-info:diagnose
```

## Version highlights

| Version | Highlights |
|---------|------------|
| **4.6** | Offline MMDB geo (`location_db`), IPv6, city/region/timezone, `client_city()` |
| **4.5** | IP risk score, per-reason filter responses, verified crawlers |
| **4.4** | Blade directives and dev components |
| **4.3** | WHOIS, threat signals, `ip.filter`, client intel logging |
| **4.2** | Global helpers, route middleware aliases |

Full changelog: [CHANGELOG.md](../CHANGELOG.md).
