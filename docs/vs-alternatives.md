# Laravel IP Info vs alternatives

Comparison for teams choosing an IP geolocation package for Laravel.

## suprun-bohdan/laravel-ip-info

**Best for:** Laravel-first apps that need request IP resolution, provider chains, offline CSV/MaxMind, caching, and testing fakes.

| Feature | Support |
|---------|---------|
| Request IP + trusted proxy presets | Yes |
| Offline IPv4 CSV | Yes |
| MaxMind GeoLite2 | Yes |
| Provider chain + custom providers | Yes |
| `IpInfo::fake()` testing | Yes |
| Negative cache | Yes |
| HTTP drivers (ip-api, ipinfo) | Yes |
| HTTP circuit breaker + 429 soft-fail | Yes |
| Geo block/allow middleware | Yes |
| Route aliases (`geo.block`, `geo.allow`) | Yes |
| Global helpers (`client_country()`, `ip_info()`) | Yes |
| Blade directives + dev components (v4.4+) | Yes |
| IP risk score + per-reason filter responses (v4.5+) | Yes |
| Verified crawler bypass for `ip.filter` (v4.5+) | Yes |
| Queue batch lookup | Yes |
| City/ASN geo in core | City via MMDB (v4.6+); ASN planned |

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

- Choose **laravel-ip-info** when country code + client IP correctness + Laravel DX (fake, middleware, diagnose) matter most.
- Choose **torann/geoip** or **stevebauman/location** when you need full geocoder-style location objects in core.

Advanced fraud/VPN signals, WHOIS, Blade DX, and IP risk scoring are available in core from v4.3+ — see the main [README](../README.md).
