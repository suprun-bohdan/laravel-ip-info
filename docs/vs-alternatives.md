# Laravel IP Info vs alternatives

Comparison for teams choosing an IP geolocation package for Laravel.

## suprun-bohdan/laravel-ip-info

**Best for:** Laravel-first apps that need request IP resolution, provider chains, offline MMDB/CSV/MaxMind, caching, and testing fakes.

| Feature | Support |
|---------|---------|
| Request IP + trusted proxy presets | Yes |
| Offline IPv4 CSV (`ip_country`) | Yes |
| **Offline MMDB IPv4 + IPv6 (v4.6+)** | **Yes** — country & city editions |
| MaxMind GeoLite2 | Yes |
| Provider chain + custom providers | Yes |
| `IpInfo::fake()` testing | Yes |
| Negative cache | Yes |
| HTTP drivers (ip-api, ipinfo) | Yes |
| HTTP circuit breaker + 429 soft-fail | Yes |
| Geo block/allow middleware | Yes |
| Route aliases (`geo.block`, `geo.allow`) | Yes |
| Global helpers (`client_country()`, `client_city()`, `ip_info()`) | Yes |
| City / region / timezone offline (v4.6+) | Yes — `location_db` + `client_city()` |
| Blade directives + dev components (v4.4+) | Yes |
| IP risk score + per-reason filter responses (v4.5+) | Yes |
| Verified crawler bypass for `ip.filter` (v4.5+) | Yes |
| Queue batch lookup | Yes |
| Benchmark / stress suite (`composer bench`) | Yes |
| ASN geo in core | Yes (v4.7+) — asn_country primary + asn enrichment |

## torann/geoip

**Best for:** MaxMind-centric apps already standardized on GeoIP2 city/country databases.

- Mature MaxMind integration and location objects with lat/long.
- Less opinionated about Laravel request IP resolution and middleware.
- Heavier focus on full geo records vs minimal country discovery.

## stevebauman/location

**Best for:** Apps needing rich location DTOs from multiple drivers (MaxMind, IpApi, etc.).

- Driver-based architecture with many third-party services.
- More HTTP dependencies and surface area than laravel-ip-info core goals.
- Good when you need city/region/timezone, not just country codes.

## Recommendation

- Choose **laravel-ip-info** when client IP correctness, Laravel DX (middleware, fake, diagnose), and **offline geo including IPv6** matter most — especially with `location_db` (v4.6+).
- Choose **torann/geoip** when you are already standardized on MaxMind GeoIP2 databases and want their location object API.
- Choose **stevebauman/location** when you prefer many HTTP drivers out of the box and full geocoder-style DTOs over a minimal provider chain.

Advanced fraud/VPN signals, WHOIS, Blade DX, IP risk scoring, and offline city geo are available in core from v4.3+ / v4.6+ — see the main [README](../README.md) and [offline geo guide](offline-geo.md).
