# Post-Refactor Audit — Laravel IP Info

Snapshot after v2.0.0 rebrand (`suprun-bohdan/laravel-ip-info` / `SuprunBohdan\IpInfo`).

## Package identity

| Item | Value |
|------|-------|
| Composer | `suprun-bohdan/laravel-ip-info` |
| Namespace | `SuprunBohdan\IpInfo` |
| Author | Bohdan Suprun |
| Config | `config/ip-info.php` |
| Facade accessor | `ip-info` |
| Env prefix | `IP_INFO_*` |
| Cache prefix (default) | `laravel_ip_info` |

## Architecture delivered

- Contracts: `IpResolver`, `IpProvider`, `IpCache`, `IpLookupContract`
- Support: `IpNormalizer`, `IpValidator`, `IpRange`
- Providers: `LocalProvider`, `DatabaseRangeProvider`, `CleanTalkProvider`, `ChainProvider`, `NullProvider`
- Cache: Laravel cache abstraction (`LaravelCacheIpCache`, `NullIpCache`)
- Laravel integration: `IpInfoServiceProvider`, `IpInfoManager`, optional HTTP route, Artisan commands

## Public API

```php
IpInfo::for('8.8.8.8')->countryCode();
IpInfo::for('8.8.8.8')->geo();
IpInfo::for('8.8.8.8')->result()->toArray();
IpInfo::forRequest($request)->ip();
```

## Release checklist status

| Item | Status |
|------|--------|
| SuprunBohdan namespace | Done |
| Tests (unit + feature) | Done (37+ tests) |
| Pint / PHPStan / Larastan config | Done |
| GitHub Actions CI | Done |
| README / CHANGELOG / LICENSE | Done |
| CI verified locally | Done (Docker sandbox) |
| Git tags | v1.0.0 – v1.3.0, v2.0.0 |
| Packagist publish | Manual |
| GitHub repo | `suprun-bohdan/laravel-ip-info` |

## v2.0 rebrand

- Removed WTG vendor identity from package name, namespace, env vars, cache prefix, and docs.
- Personal maintainer package under `suprun-bohdan/laravel-ip-info`.

## Known limitations

- Offline DB: IPv4 only
- CleanTalk optional, disabled by default
- No city/region/ASN geo
- No timezone fallback

## Postponed (future)

- IPv6 offline database
- Additional HTTP geo providers beyond CleanTalk
- Configurable bogon filtering

See [migration-guide.md](migration-guide.md).
