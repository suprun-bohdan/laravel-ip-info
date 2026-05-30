<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

use SuprunBohdan\IpInfo\Exceptions\ProviderException;

final class UrlAllowlistGuard
{
    /**
     * @param  list<string>  $allowedHosts
     */
    public static function assertAllowlisted(string $urlTemplate, array $allowedHosts): void
    {
        $host = parse_url($urlTemplate, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            throw new ProviderException('URL template must include a host.');
        }

        $normalized = strtolower($host);
        $allowed = array_map(static fn (string $h): string => strtolower($h), $allowedHosts);

        if (! in_array($normalized, $allowed, true)) {
            throw new ProviderException(
                'URL host ['.$host.'] is not allowlisted. Allowed: '.implode(', ', $allowedHosts)
            );
        }
    }
}
