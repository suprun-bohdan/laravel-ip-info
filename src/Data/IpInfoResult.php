<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use JsonSerializable;
use SuprunBohdan\IpInfo\Exceptions\IpInfoException;
use SuprunBohdan\IpInfo\Support\IpNormalizer;

final readonly class IpInfoResult implements JsonSerializable
{
    public function __construct(
        public string $ip,
        public GeoLocation $geo,
        public bool $isPublic,
        public bool $isPrivate,
        public ?string $provider = null,
        public ?IpThreatSignals $threats = null,
    ) {}

    public function countryCode(): ?string
    {
        return $this->geo->countryCode;
    }

    public function isEu(): bool
    {
        return $this->geo->isEu();
    }

    public function isInContinent(string $continent): bool
    {
        return $this->geo->isInContinent($continent);
    }

    public function countryNameOrCode(): ?string
    {
        return $this->geo->countryNameOrCode();
    }

    public function isCountry(string ...$codes): bool
    {
        $country = $this->countryCode();

        if ($country === null) {
            return false;
        }

        $normalized = strtoupper($country);

        foreach ($codes as $code) {
            if (strtoupper($code) === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $codes
     */
    public function inCountries(array $codes): bool
    {
        return $this->isCountry(...$codes);
    }

    public function countryOr(?string $default): ?string
    {
        return $this->countryCode() ?? $default;
    }

    public function countryOrFail(string $message = 'Unable to resolve country for IP address.'): string
    {
        $country = $this->countryCode();

        if ($country === null) {
            throw new IpInfoException($message);
        }

        return $country;
    }

    public function city(): ?string
    {
        return $this->geo->city;
    }

    public function region(): ?string
    {
        return $this->geo->region;
    }

    public function timezone(): ?string
    {
        return $this->geo->timezone;
    }

    public function asn(): ?int
    {
        return $this->geo->autonomousSystemNumber;
    }

    public function asnOrganization(): ?string
    {
        return $this->geo->autonomousSystemOrganization;
    }

    public function isAsn(int ...$asns): bool
    {
        $asn = $this->asn();

        if ($asn === null) {
            return false;
        }

        return in_array($asn, $asns, true);
    }

    public function isCity(string ...$cities): bool
    {
        $city = $this->city();

        if ($city === null) {
            return false;
        }

        foreach ($cities as $candidate) {
            if (strcasecmp($candidate, $city) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    public function coordinates(): ?array
    {
        return $this->geo->coordinates();
    }

    /**
     * @deprecated Use {@see IpPrivacyPolicy::shouldLog()} instead.
     */
    public function shouldLog(): bool
    {
        if ($this->isPrivate) {
            return false;
        }

        return true;
    }

    public function isTor(): bool
    {
        return $this->threats?->isTor() ?? false;
    }

    public function isProxy(): bool
    {
        return $this->threats?->isProxy() ?? false;
    }

    public function isVpn(): bool
    {
        return $this->threats?->isVpn() ?? false;
    }

    public function isHosting(): bool
    {
        return $this->threats?->isHosting() ?? false;
    }

    public function isAnonymous(): bool
    {
        return $this->threats?->isAnonymous() ?? false;
    }

    public function anonymized(): self
    {
        return new self(
            $this->anonymizeIp($this->ip),
            $this->geo,
            $this->isPublic,
            $this->isPrivate,
            $this->provider,
            $this->threats,
        );
    }

    public function forLogging(): self
    {
        $geo = $this->isPrivate
            ? GeoLocation::fromCountryCode(null)
            : $this->geo;

        return new self(
            $this->anonymizeIp($this->ip),
            $geo,
            $this->isPublic,
            $this->isPrivate,
            $this->provider,
            $this->threats,
        );
    }

    /**
     * @return array{country: ?string, is_public: bool}
     */
    public function toMinimalArray(): array
    {
        return [
            'country' => $this->countryCode(),
            'is_public' => $this->isPublic,
        ];
    }

    /**
     * @return array{
     *     ip: string,
     *     country: ?string,
     *     country_name: ?string,
     *     continent: ?string,
     *     is_eu: ?bool,
     *     is_public: bool,
     *     is_private: bool,
     *     provider: ?string,
     *     city: ?string,
     *     region: ?string,
     *     region2: ?string,
     *     postcode: ?string,
     *     latitude: ?float,
     *     longitude: ?float,
     *     timezone: ?string,
     *     threats: ?array{
     *         tor: ?bool,
     *         proxy: ?bool,
     *         vpn: ?bool,
     *         hosting: ?bool,
     *         source: ?string
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'country' => $this->countryCode(),
            'country_name' => $this->geo->countryName,
            'continent' => $this->geo->continent,
            'is_eu' => $this->geo->isEu,
            'is_public' => $this->isPublic,
            'is_private' => $this->isPrivate,
            'provider' => $this->provider,
            'city' => $this->geo->city,
            'region' => $this->geo->region,
            'region2' => $this->geo->region2,
            'postcode' => $this->geo->postcode,
            'latitude' => $this->geo->latitude,
            'longitude' => $this->geo->longitude,
            'timezone' => $this->geo->timezone,
            'autonomous_system_number' => $this->geo->autonomousSystemNumber,
            'autonomous_system_organization' => $this->geo->autonomousSystemOrganization,
            'threats' => $this->threats?->toArray(),
        ];
    }

    /**
     * @return array{
     *     ip: string,
     *     country: ?string,
     *     country_name: ?string,
     *     continent: ?string,
     *     is_eu: ?bool,
     *     is_public: bool,
     *     is_private: bool,
     *     provider: ?string,
     *     city: ?string,
     *     region: ?string,
     *     region2: ?string,
     *     postcode: ?string,
     *     latitude: ?float,
     *     longitude: ?float,
     *     timezone: ?string,
     *     threats: ?array{
     *         tor: ?bool,
     *         proxy: ?bool,
     *         vpn: ?bool,
     *         hosting: ?bool,
     *         source: ?string
     *     }
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function anonymizeIp(string $ip): string
    {
        return (new IpNormalizer)->anonymize($ip);
    }
}
