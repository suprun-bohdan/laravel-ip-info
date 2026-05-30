<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Resolvers;

use SuprunBohdan\IpInfo\Contracts\IpResolver;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Exceptions\InvalidIpAddressException;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class StringIpResolver implements IpResolver
{
    public function __construct(
        private IpNormalizer $normalizer,
        private IpValidator $validator,
    ) {}

    public function resolve(mixed $source): IpAddress
    {
        if (! is_string($source)) {
            throw new InvalidIpAddressException('Expected string IP address.');
        }

        $normalized = $this->normalizer->normalize($source);

        if (! $this->validator->isValid($normalized)) {
            throw new InvalidIpAddressException("Invalid IP address: {$source}");
        }

        return new IpAddress($normalized);
    }
}
