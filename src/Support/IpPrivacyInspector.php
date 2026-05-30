<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Exceptions\InvalidIpAddressException;

final class IpPrivacyInspector
{
    public function __construct(
        private IpNormalizer $normalizer,
        private IpValidator $validator,
    ) {}

    public function profile(string $ip): IpPrivacyProfile
    {
        $normalized = $this->normalizer->normalize($ip);

        if (! $this->validator->isValid($normalized)) {
            throw new InvalidIpAddressException("Invalid IP address: {$ip}");
        }

        return new IpPrivacyProfile(
            ip: $ip,
            normalizedIp: $normalized,
            isValid: true,
            isPublic: $this->validator->isPublic($normalized),
            isPrivate: $this->validator->isPrivate($normalized),
            isLocalhost: $this->validator->isLocalhost($normalized),
            isLinkLocal: $this->validator->isLinkLocal($normalized),
            isReserved: $this->validator->isReserved($normalized),
            shouldSkipExternalLookup: $this->validator->shouldSkipExternalLookup($normalized),
        );
    }
}
