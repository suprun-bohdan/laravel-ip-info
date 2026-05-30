<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Support;

use Illuminate\Http\Client\Factory;

final class CloudflareCidrFetcher
{
    private const IPV4_URL = 'https://www.cloudflare.com/ips-v4';

    private const IPV6_URL = 'https://www.cloudflare.com/ips-v6';

    public function __construct(private Factory $http) {}

    /**
     * @return list<string>
     */
    public function fetchAll(): array
    {
        $v4 = $this->fetchList(self::IPV4_URL);
        $v6 = $this->fetchList(self::IPV6_URL);

        return array_values(array_unique(array_merge($v4, $v6)));
    }

    public function toEnvSnippet(array $cidrs): string
    {
        return 'IP_INFO_TRUSTED_PROXY_CIDRS='.implode(',', $cidrs);
    }

    /**
     * @return list<string>
     */
    private function fetchList(string $url): array
    {
        $response = $this->http
            ->timeout(10)
            ->withOptions(['allow_redirects' => false])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch Cloudflare CIDR list from '.$url.'.');
        }

        return $this->parseCidrs((string) $response->body());
    }

    /**
     * @return list<string>
     */
    private function parseCidrs(string $body): array
    {
        $body = trim($body);
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $cidrs = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if ($this->isValidCidr($line)) {
                $cidrs[] = $line;
            }
        }

        return $cidrs;
    }

    private function isValidCidr(string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return false;
        }

        [$ip, $prefix] = explode('/', $cidr, 2);

        if ($ip === '' || $prefix === '' || ! ctype_digit($prefix)) {
            return false;
        }

        $flags = str_contains($ip, ':') ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4;

        return filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false;
    }
}
