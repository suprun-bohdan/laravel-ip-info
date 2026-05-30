# Packagist publish checklist

Manual steps to publish `suprun-bohdan/laravel-ip-info` on Packagist.

## Prerequisites

1. GitHub repository: `https://github.com/suprun-bohdan/laravel-ip-info`
2. Packagist account linked to GitHub
3. Tagged release on GitHub (e.g. `v2.1.0`)

## Steps

1. Sign in at [packagist.org](https://packagist.org) and submit the repository URL.
2. Enable the GitHub webhook (Packagist UI → package → Settings → GitHub Hook).
3. After each tag push, Packagist auto-updates the package index.

```bash
git tag v2.1.0
git push origin v2.1.0
```

4. Verify install:

```bash
composer require suprun-bohdan/laravel-ip-info:^2.1
```

## Post-publish

- Confirm README badges resolve (Packagist version, GitHub Actions).
- Run `composer validate --strict` in CI before tagging.
