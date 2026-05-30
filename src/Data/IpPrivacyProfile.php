<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use JsonSerializable;
use SuprunBohdan\IpInfo\Privacy\IpPrivacyPolicy;

final readonly class IpPrivacyProfile implements JsonSerializable
{
    public function __construct(
        public string $ip,
        public string $normalizedIp,
        public bool $isValid,
        public bool $isPublic,
        public bool $isPrivate,
        public bool $isLocalhost,
        public bool $isLinkLocal,
        public bool $isReserved,
        public bool $shouldSkipExternalLookup,
    ) {}

    public function anonymizedIp(): string
    {
        return (new IpInfoResult(
            $this->normalizedIp,
            GeoLocation::fromCountryCode(null),
            $this->isPublic,
            $this->isPrivate,
        ))->anonymized()->ip;
    }

    public function shouldLog(?IpPrivacyPolicy $policy = null): bool
    {
        $policy ??= new IpPrivacyPolicy;

        return $policy->shouldLog(new IpInfoResult(
            $this->normalizedIp,
            GeoLocation::fromCountryCode(null),
            $this->isPublic,
            $this->isPrivate,
        ));
    }

    /**
     * @return array{
     *     ip: string,
     *     normalized_ip: string,
     *     is_valid: bool,
     *     is_public: bool,
     *     is_private: bool,
     *     is_localhost: bool,
     *     is_link_local: bool,
     *     is_reserved: bool,
     *     should_skip_external_lookup: bool,
     *     anonymized_ip: string
     * }
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'normalized_ip' => $this->normalizedIp,
            'is_valid' => $this->isValid,
            'is_public' => $this->isPublic,
            'is_private' => $this->isPrivate,
            'is_localhost' => $this->isLocalhost,
            'is_link_local' => $this->isLinkLocal,
            'is_reserved' => $this->isReserved,
            'should_skip_external_lookup' => $this->shouldSkipExternalLookup,
            'anonymized_ip' => $this->anonymizedIp(),
        ];
    }

    /**
     * @return array{
     *     ip: string,
     *     normalized_ip: string,
     *     is_valid: bool,
     *     is_public: bool,
     *     is_private: bool,
     *     is_localhost: bool,
     *     is_link_local: bool,
     *     is_reserved: bool,
     *     should_skip_external_lookup: bool,
     *     anonymized_ip: string
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
