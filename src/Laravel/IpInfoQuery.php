<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel;

use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;
use SuprunBohdan\IpInfo\Data\WhoisRecord;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;

final class IpInfoQuery
{
    private ?IpInfoResult $resolved = null;

    public function __construct(
        private IpLookupContract $manager,
        private IpAddress $address,
    ) {}

    public function ip(): string
    {
        return $this->address->value;
    }

    public function countryCode(): ?string
    {
        return $this->result()->countryCode();
    }

    public function geo(): GeoLocation
    {
        return $this->result()->geo;
    }

    public function isPublic(): bool
    {
        return $this->manager->isPublic($this->address);
    }

    public function isPrivate(): bool
    {
        return $this->manager->isPrivate($this->address);
    }

    public function normalizedIp(): string
    {
        return $this->manager->normalizedIp($this->address);
    }

    public function privacy(): IpPrivacyProfile
    {
        return $this->manager->privacyProfile($this->address);
    }

    public function threats(): IpThreatSignals
    {
        return $this->result()->threats ?? $this->manager->threatSignals($this->address);
    }

    public function isTor(): bool
    {
        return $this->threats()->isTor();
    }

    public function isProxy(): bool
    {
        return $this->threats()->isProxy();
    }

    public function isVpn(): bool
    {
        return $this->threats()->isVpn();
    }

    public function isHosting(): bool
    {
        return $this->threats()->isHosting();
    }

    public function isAnonymous(): bool
    {
        return $this->threats()->isAnonymous();
    }

    public function whois(bool $force = false): ?WhoisRecord
    {
        return app(\SuprunBohdan\IpInfo\Whois\WhoisLookupService::class)->lookup($this->ip(), $force);
    }

    public function intel(bool $withWhois = false): ClientIpIntel
    {
        return app(\SuprunBohdan\IpInfo\Intel\ClientIpIntelBuilder::class)->fromResult(
            $this->result(),
            $this->privacy(),
            $withWhois,
        );
    }

    public function result(): IpInfoResult
    {
        if ($this->resolved === null) {
            $this->resolved = $this->manager->lookup($this->address);
        }

        return $this->resolved;
    }

    public function isCountry(string ...$codes): bool
    {
        return $this->result()->isCountry(...$codes);
    }

    /**
     * @param  list<string>  $codes
     */
    public function inCountries(array $codes): bool
    {
        return $this->result()->inCountries($codes);
    }

    public function isEu(): bool
    {
        return $this->result()->isEu();
    }

    public function isInContinent(string $continent): bool
    {
        return $this->result()->isInContinent($continent);
    }

    public function countryOr(?string $default): ?string
    {
        return $this->result()->countryOr($default);
    }

    public function countryOrFail(string $message = 'Unable to resolve country for IP address.'): string
    {
        return $this->result()->countryOrFail($message);
    }

    public function city(): ?string
    {
        return $this->result()->city();
    }

    public function region(): ?string
    {
        return $this->result()->region();
    }

    public function timezone(): ?string
    {
        return $this->result()->timezone();
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    public function coordinates(): ?array
    {
        return $this->result()->coordinates();
    }

    public function asn(): ?int
    {
        return $this->result()->asn();
    }

    public function asnOrganization(): ?string
    {
        return $this->result()->asnOrganization();
    }

    public function isAsn(int ...$asns): bool
    {
        return $this->result()->isAsn(...$asns);
    }

    public function isCity(string ...$cities): bool
    {
        return $this->result()->isCity(...$cities);
    }
}
