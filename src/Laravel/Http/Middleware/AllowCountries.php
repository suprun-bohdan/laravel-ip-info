<?php

// @ip-info-stub-version 4.2.0

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use Symfony\Component\HttpFoundation\Response;

final class AllowCountries
{
    public function handle(Request $request, Closure $next, string ...$countries): Response
    {
        $allowed = $countries !== []
            ? $countries
            : config('ip-info.security.allowed_countries', []);

        if (! is_array($allowed) || $allowed === []) {
            return $next($request);
        }

        $country = IpInfo::forRequest($request)->countryCode();
        $normalizedAllowed = array_map(static fn (string $code): string => strtoupper($code), $allowed);

        if ($country === null || ! in_array(strtoupper($country), $normalizedAllowed, true)) {
            abort(
                (int) config('ip-info.security.block_response_status', 403),
                (string) config('ip-info.security.block_response_message', 'Access from your country is not allowed.'),
            );
        }

        return $next($request);
    }
}
