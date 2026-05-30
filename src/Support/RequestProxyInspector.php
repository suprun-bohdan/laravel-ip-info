<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

use Illuminate\Http\Request;

final class RequestProxyInspector
{
    public function isBehindTrustedProxy(Request $request): bool
    {
        $remote = $request->server('REMOTE_ADDR');

        if (! is_string($remote) || $remote === '') {
            return false;
        }

        if (! (bool) config('ip-info.trusted_proxies.require_trusted_proxy_for_headers', true)) {
            return $this->hasTrustedHeaderValue($request);
        }

        $cidrs = config('ip-info.trusted_proxies.proxy_cidrs', []);

        if (! is_array($cidrs) || $cidrs === []) {
            return false;
        }

        return CidrMatcher::matchesAny($remote, $cidrs);
    }

    public function hasTrustedHeaderValue(Request $request): bool
    {
        $headers = config('ip-info.trusted_proxies.headers', []);

        if (! is_array($headers)) {
            return false;
        }

        foreach ($headers as $header) {
            if (! is_string($header) || $header === '') {
                continue;
            }

            $value = $request->header($header);

            if (is_string($value) && $value !== '') {
                return true;
            }
        }

        return false;
    }
}
