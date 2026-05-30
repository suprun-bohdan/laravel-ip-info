<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Resolvers;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Contracts\IpResolver;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Exceptions\InvalidIpAddressException;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class RequestIpResolver implements IpResolver
{
    public function __construct(
        private IpNormalizer $normalizer,
        private IpValidator $validator,
    ) {}

    public function resolve(mixed $source): IpAddress
    {
        if (! $source instanceof Request) {
            throw new InvalidIpAddressException('Expected Illuminate\Http\Request.');
        }

        $trustedHeaders = config('ip-info.trusted_proxies.headers', []);

        foreach ($trustedHeaders as $header) {
            $value = $source->header($header);

            if (! is_string($value) || $value === '') {
                continue;
            }

            $candidate = $this->normalizer->normalize($this->firstAddress($value));

            if ($this->validator->isValid($candidate)) {
                return new IpAddress($candidate);
            }
        }

        if (config('ip-info.trusted_proxies.respect_laravel', true)) {
            $ip = $source->ip();

            if (is_string($ip) && $ip !== '' && $this->validator->isValid($ip)) {
                return new IpAddress($this->normalizer->normalize($ip));
            }
        }

        throw new InvalidIpAddressException('Unable to resolve client IP address.');
    }

    private function firstAddress(string $value): string
    {
        $parts = explode(',', $value);

        return trim($parts[0]);
    }
}
