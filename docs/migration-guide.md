# Migration Guide — Laravel IP Info

Guide for upgrading from legacy package versions or pre-refactor installs.

## Package identity

| Legacy / interim | Current (v2.0+) |
|------------------|-----------------|
| `wtg/laravel-ip-info` | `suprun-bohdan/laravel-ip-info` |
| `Wtg\IpInfo` | `SuprunBohdan\IpInfo` |
| `SuprunBohdan\LaravelIpInfo` (pre-refactor) | `SuprunBohdan\IpInfo` |
| `config/laravel-ip-info.php` | `config/ip-info.php` |
| `IPCheckService` facade/service | `IpInfo` facade + `IpInfoManager` |

## Config key migration

Replace published config and `.env` keys:

| Legacy / old | New |
|--------------|-----|
| `WTG_IP_INFO_*` | `IP_INFO_*` |
| `LARAVEL_IP_INFO_*` | `IP_INFO_*` |
| `wtg_ip_info` cache prefix | `laravel_ip_info` |
| `laravel-ip-info.php` | `ip-info.php` |

Run:

```bash
php artisan vendor:publish --tag=ip-info-config --force
```

Review `config/ip-info.php` and copy values from the old file manually.

## API migration

### Before (legacy)

```php
// Removed — do not use
$country = app('IPCheckService')->getCountry($ip);
```

### After (v1.x)

```php
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

$country = IpInfo::for($ip)->countryCode();
$requestIp = IpInfo::forRequest($request)->ip();
$result = IpInfo::for($ip)->result(); // IpInfoResult DTO
```

## Breaking changes since v1.0

1. HTTP route is **opt-in** (`IP_INFO_ROUTES_ENABLED=false` by default).
2. No timezone-to-country fallback.
3. No direct Redis/Predis dependency — uses Laravel cache.
4. Trusted proxy headers are **not** read unless listed in `ip-info.trusted_proxies.headers`.
5. Offline database is IPv4-only.

### Upgrading to 2.0

1. Update `composer.json`: `composer require suprun-bohdan/laravel-ip-info:^2.0`
2. Replace all `use Wtg\IpInfo\...` imports with `use SuprunBohdan\IpInfo\...`
3. Rename `.env` keys from `WTG_IP_INFO_*` to `IP_INFO_*`
4. Clear config cache and republish config if needed

## Upgrading 1.x → 1.2

### 1.1

- `IpInfoResult::countryCode()` method (not property).
- `ip-info:update-database` command added.
- Provider extension via `providers.custom` and `IpInfoBuildingChain` event.

### 1.2

- RFC 5737 TEST-NET ranges treated as reserved (skip external lookup).
- IPv6 `::` and multicast (`ff00::/8`) skip external lookup.
- `IpInfoResult::toArray()` / `JsonSerializable`.
- `ip-info:diagnose --json`.
- CleanTalk URL host allowlist (`api.cleantalk.org` only).
- `IpLookupContract` for extension/testing.

### 1.3

- Documentation and DX improvements (README, migration guide, dev sandbox notes).
- IPv6 offline DB and additional HTTP providers remain planned for future releases.

## Seeder namespace (1.1.1 fix)

If you referenced the seeder class directly, update imports:

```php
use SuprunBohdan\IpInfo\Laravel\Database\Seeders\IpCountrySeeder;
```

PSR-4 path: `src/Laravel/Database/Seeders/IpCountrySeeder.php`.

## v2.1+ features

| Feature | Usage |
|---------|-------|
| Testing fake | `IpInfo::fake(['8.8.8.8' => 'US'])` |
| Middleware | `ResolveClientIp` + `--tag=ip-info-middleware` |
| Install wizard | `php artisan ip-info:install --preset=cloudflare` |
| MaxMind | `IP_INFO_MAXMIND_ENABLED=true` + `ip-info:update-maxmind` |
| HTTP drivers | `IP_INFO_HTTP_ENABLED=true`, `IP_INFO_HTTP_DRIVER=ip-api` |
| Batch lookup | `IpInfo::forMany(['8.8.8.8', '1.1.1.1'])` |

## Verification checklist

```bash
composer test
composer analyse
php artisan ip-info:diagnose --json
php artisan ip-info:diagnose 8.8.8.8
```

For local integration testing (not shipped in package):

```bash
cd sandbox && make init && make test
```
