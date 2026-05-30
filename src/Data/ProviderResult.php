<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use SuprunBohdan\IpInfo\Contracts\IpProvider;

/**
 * Result returned by a single {@see IpProvider}.
 */
final readonly class ProviderResult
{
    /**
     * @deprecated Use {@see $status} instead.
     */
    public bool $resolved;

    public function __construct(
        public ?string $countryCode,
        public string $provider,
        public ProviderStatus $status,
        public ?GeoLocation $geo = null,
        public ?string $reason = null,
    ) {
        $this->resolved = $status === ProviderStatus::Hit || $status === ProviderStatus::Miss;
    }

    public function isHit(): bool
    {
        return $this->status === ProviderStatus::Hit;
    }

    public function isMiss(): bool
    {
        return $this->status === ProviderStatus::Miss;
    }

    public function shouldStopChain(): bool
    {
        return $this->status === ProviderStatus::Hit || $this->status === ProviderStatus::Miss;
    }

    public function geoLocation(): GeoLocation
    {
        return $this->geo ?? GeoLocation::fromCountryCode($this->countryCode);
    }

    public static function skipped(string $provider, ?string $reason = null): self
    {
        return new self(null, $provider, ProviderStatus::Skipped, null, $reason);
    }

    public static function failed(string $provider, ?string $reason = null): self
    {
        return new self(null, $provider, ProviderStatus::Failed, null, $reason);
    }

    public static function miss(string $provider, ?string $reason = null): self
    {
        return new self(null, $provider, ProviderStatus::Miss, null, $reason);
    }

    public static function hit(
        string $countryCode,
        string $provider,
        ?GeoLocation $geo = null,
        ?string $reason = null,
    ): self {
        return new self($countryCode, $provider, ProviderStatus::Hit, $geo, $reason);
    }

    /**
     * Backward-compatible factory matching legacy `(country, provider, resolved)` calls.
     */
    public static function fromLegacy(
        ?string $countryCode,
        string $provider,
        bool $resolved,
        ?GeoLocation $geo = null,
    ): self {
        if (! $resolved) {
            return self::skipped($provider);
        }

        if ($countryCode !== null && $countryCode !== '') {
            return self::hit($countryCode, $provider, $geo);
        }

        return self::miss($provider);
    }
}
