# Satellite packages plan

Core `suprun-bohdan/laravel-ip-info` intentionally stays minimal. Advanced capabilities live in separate repositories.

## Planned satellites

| Package | Purpose | MVP scope |
|---------|---------|-----------|
| `laravel-ip-info-asn` | ASN lookup | Team Cymru DNS + optional MaxMind ASN MMDB |
| `laravel-ip-info-fraud` | VPN/proxy/TOR signals | HTTP driver flags from ip-api/ipinfo only |
| `laravel-ip-info-pulse-ui` | Rich Pulse dashboard | Extends core `IpInfoCard` with country charts |

## Integration pattern

1. Listen to `IpLookupCompleted` / `IpLookupsBatchCompleted`.
2. Register providers via `IpInfoBuildingChain` event.
3. Publish own config; never patch core chain defaults.

## laravel-ip-info-asn (MVP)

```php
// Registers AsnProvider via IpInfoBuildingChain
final class AsnProvider implements IpProvider
{
    public function lookup(IpAddress $ip): ProviderResult
    {
        // Team Cymru: origin.asn.cymru.com TXT lookup or MMDB-ASN
    }
}
```

## laravel-ip-info-fraud (MVP)

```php
// Listens to IpLookupCompleted, adds risk score attribute — not in core chain
final class FraudScoreListener
{
    public function handle(IpLookupCompleted $event): void
    {
        // Optional HTTP enrichment; store in separate cache key
    }
}
```

## Non-goals for core

- ML risk models, full geocoder, VPN in default chain, 10+ HTTP providers.
