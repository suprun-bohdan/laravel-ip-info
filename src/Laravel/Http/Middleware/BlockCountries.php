<?php

// @ip-info-stub-version 4.5.0

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use Symfony\Component\HttpFoundation\Response;

final class BlockCountries
{
    public function handle(Request $request, Closure $next, string ...$countries): Response
    {
        $blocked = $countries !== []
            ? $countries
            : config('ip-info.security.blocked_countries', []);

        if (! is_array($blocked) || $blocked === []) {
            return $next($request);
        }

        $country = IpInfo::forRequest($request)->countryCode();

        if ($country !== null && in_array(strtoupper($country), array_map('strtoupper', $blocked), true)) {
            abort(
                (int) config('ip-info.security.block_response_status', 403),
                (string) config('ip-info.security.block_response_message', 'Access from your country is not allowed.'),
            );
        }

        return $next($request);
    }
}
