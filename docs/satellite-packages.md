# Satellite packages plan

Core `suprun-bohdan/laravel-ip-info` intentionally stays minimal: country discovery, request IP resolution, caching, and provider chains.

Advanced capabilities belong in **separate repositories** that depend on this package.

## Planned satellites (not in core)

| Package | Purpose | Depends on |
|---------|---------|------------|
| `laravel-ip-info-fraud` | VPN/proxy/TOR risk scoring hooks | core + external APIs |
| `laravel-ip-info-asn` | ASN lookup via RIPE/Team Cymru | core |
| `laravel-ip-info-pulse-ui` | Rich Pulse dashboard cards | core + laravel/pulse |

## Non-goals for core (v3.0+)

- Machine learning risk models
- Full geocoder (city lat/long as primary API)
- 10+ bundled HTTP providers
- VPN detection in core chain

## Integration pattern

Satellite packages should:

1. Listen to `IpLookupCompleted` events from core.
2. Register custom `IpProvider` implementations via `IpInfoBuildingChain`.
3. Publish their own config and migrations.

This keeps core stable while the ecosystem grows.
