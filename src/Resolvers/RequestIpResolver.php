<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Resolvers;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Contracts\IpResolver;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Exceptions\InvalidIpAddressException;
use SuprunBohdan\IpInfo\Support\CidrMatcher;
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

        if ($this->shouldReadTrustedHeaders($source)) {
            $trustedHeaders = config('ip-info.trusted_proxies.headers', []);

            if (is_array($trustedHeaders)) {
                foreach ($trustedHeaders as $header) {
                    if (! is_string($header) || $header === '') {
                        continue;
                    }

                    $value = $source->header($header);

                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    $candidate = $this->normalizer->normalize($this->firstAddress($value));

                    if ($this->validator->isValid($candidate)) {
                        return new IpAddress($candidate);
                    }
                }
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

    private function shouldReadTrustedHeaders(Request $request): bool
    {
        $requireTrustedProxy = (bool) config('ip-info.trusted_proxies.require_trusted_proxy_for_headers', true);

        if (! $requireTrustedProxy) {
            return true;
        }

        $remote = $request->server('REMOTE_ADDR');

        if (! is_string($remote) || $remote === '') {
            return false;
        }

        $cidrs = config('ip-info.trusted_proxies.proxy_cidrs', []);

        if (! is_array($cidrs) || $cidrs === []) {
            return false;
        }

        return CidrMatcher::matchesAny($remote, $cidrs);
    }

    private function firstAddress(string $value): string
    {
        $parts = explode(',', $value);

        return trim($parts[0]);
    }
}
