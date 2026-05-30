<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use JsonSerializable;
use SuprunBohdan\IpInfo\Support\IpNormalizer;

final readonly class IpInfoResult implements JsonSerializable
{
    public function __construct(
        public string $ip,
        public GeoLocation $geo,
        public bool $isPublic,
        public bool $isPrivate,
        public ?string $provider = null,
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

    public function anonymized(): self
    {
        return new self(
            $this->anonymizeIp($this->ip),
            $this->geo,
            $this->isPublic,
            $this->isPrivate,
            $this->provider,
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
     *     provider: ?string
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
     *     provider: ?string
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);

            return implode('.', [$parts[0], $parts[1], $parts[2], '0']);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $normalized = (new IpNormalizer)->normalize($ip);
            $segments = explode(':', $normalized);

            return implode(':', array_slice($segments, 0, 4)).'::';
        }

        return $ip;
    }
}
