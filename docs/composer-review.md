# Composer Review — Laravel IP Info

## Current composer.json problems

| Issue | Impact |
|-------|--------|
| Package name `suprun-bohdan/laravel-ip-info` | Does not match target `suprun-bohdan/laravel-ip-info` |
| `predis/predis` in `require` | Forces Redis client; bypasses Laravel cache |
| Missing `orchestra/testbench` | Tests cannot run |
| Missing `illuminate/http`, `illuminate/cache` | Implicit peers only via support |
| `minimum-stability: dev` | Allows unstable deps without reason |
| PHP `^8.0` / Laravel 8–10 | Below modern target; enums/unions need 8.1+ |
| Only `test` script | No format/analyse/validate |
| No `suggest` section | Optional deps not documented |

## Proposed composer.json

```json
{
  "name": "suprun-bohdan/laravel-ip-info",
  "description": "Laravel package for IP detection, normalization, request IP resolution, geo lookup, caching, and infrastructure-aware IP intelligence.",
  "type": "library",
  "license": "MIT",
  "authors": [
    {
      "name": "Bohdan Suprun",
      "email": "bohdan-suprun@outlook.com"
    }
  ],
  "require": {
    "php": "^8.2",
    "illuminate/support": "^10.0|^11.0|^12.0",
    "illuminate/http": "^10.0|^11.0|^12.0",
    "illuminate/cache": "^10.0|^11.0|^12.0"
  },
  "require-dev": {
    "orchestra/testbench": "^8.0|^9.0|^10.0",
    "phpunit/phpunit": "^10.5|^11.0",
    "mockery/mockery": "^1.6",
    "laravel/pint": "^1.0",
    "phpstan/phpstan": "^2.0",
    "illuminate/database": "^10.0|^11.0|^12.0"
  },
  "autoload": {
    "psr-4": {
      "SuprunBohdan\\IpInfo\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "SuprunBohdan\\IpInfo\\Tests\\": "tests/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "SuprunBohdan\\IpInfo\\Laravel\\IpInfoServiceProvider"
      ],
      "aliases": {
        "IpInfo": "SuprunBohdan\\IpInfo\\Laravel\\Facades\\IpInfo"
      }
    }
  },
  "scripts": {
    "test": "vendor/bin/phpunit",
    "analyse": "vendor/bin/phpstan analyse",
    "format": "vendor/bin/pint",
    "format:test": "vendor/bin/pint --test",
    "validate": "composer validate --strict"
  },
  "suggest": {
    "ext-redis": "Required when using the redis cache store",
    "illuminate/database": "Required for DatabaseRangeProvider offline IPv4 lookup"
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

## Reason for each dependency

### require

| Package | Reason |
|---------|--------|
| `php ^8.2` | Typed properties, readonly, enums; active security support |
| `illuminate/support` | ServiceProvider, facades, config merge |
| `illuminate/http` | Request IP resolution, optional HTTP controller |
| `illuminate/cache` | Cache abstraction for `LaravelCacheIpCache` |

### require-dev

| Package | Reason |
|---------|--------|
| `orchestra/testbench` | Laravel package integration tests |
| `phpunit/phpunit` | Test runner |
| `mockery/mockery` | Mock HTTP/cache in unit tests |
| `laravel/pint` | PSR-12 formatting |
| `phpstan/phpstan` | Static analysis |
| `illuminate/database` | Migration/seeder/DatabaseRangeProvider tests |

### Removed

| Package | Reason |
|---------|--------|
| `predis/predis` | Replaced by Laravel cache stores |

## Supported PHP/Laravel matrix

| PHP | Laravel | Testbench |
|-----|---------|-----------|
| 8.2 | 10.x | ^8.0 |
| 8.2 | 11.x | ^9.0 |
| 8.3 | 10.x | ^8.0 |
| 8.3 | 11.x | ^9.0 |
| 8.2+ | 12.x | ^10.0 |

CI runs PHP 8.2 and 8.3 against Laravel 10 and 11 initially. Laravel 12 added when Testbench stable in workflow matrix.

## Compatibility decisions

1. **Drop Laravel 8/9:** No Testbench alignment benefit; reduces matrix cost.
2. **Drop PHP 8.0/8.1:** Package uses strict typing and modern syntax throughout refactor.
3. **Database optional:** In `suggest` + dev; offline DB requires host app database config.
4. **No version field in composer.json:** Releases tagged via Git only.
