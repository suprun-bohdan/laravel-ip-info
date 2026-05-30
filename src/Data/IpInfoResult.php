<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use JsonSerializable;

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

    /**
     * @return array{ip: string, country: ?string, is_public: bool, is_private: bool, provider: ?string}
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'country' => $this->countryCode(),
            'is_public' => $this->isPublic,
            'is_private' => $this->isPrivate,
            'provider' => $this->provider,
        ];
    }

    /**
     * @return array{ip: string, country: ?string, is_public: bool, is_private: bool, provider: ?string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
