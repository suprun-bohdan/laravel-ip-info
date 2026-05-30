<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Intel;

use JsonSerializable;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;
use SuprunBohdan\IpInfo\Data\WhoisRecord;
use SuprunBohdan\IpInfo\Privacy\IpPrivacyPolicy;

final readonly class ClientIpIntel implements JsonSerializable
{
    public function __construct(
        public IpInfoResult $geo,
        public IpPrivacyProfile $privacy,
        public ?WhoisRecord $whois = null,
    ) {}

    public function threats(): ?IpThreatSignals
    {
        return $this->geo->threats;
    }

    public function forLogging(bool $includeWhois = true, bool $includeThreats = true): array
    {
        $payload = $this->geo->forLogging()->toArray();

        $payload['privacy'] = [
            'is_localhost' => $this->privacy->isLocalhost,
            'is_link_local' => $this->privacy->isLinkLocal,
            'is_reserved' => $this->privacy->isReserved,
            'should_skip_external_lookup' => $this->privacy->shouldSkipExternalLookup,
        ];

        if ($includeThreats && $this->geo->threats !== null) {
            $payload['threats'] = $this->geo->threats->toArray();
        }

        if ($includeWhois && $this->whois !== null) {
            $payload['whois'] = $this->whois->toArray();
        }

        return $payload;
    }

    public function shouldLog(?IpPrivacyPolicy $policy = null): bool
    {
        $policy ??= new IpPrivacyPolicy;

        return $policy->shouldLog($this->geo);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->forLogging();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
