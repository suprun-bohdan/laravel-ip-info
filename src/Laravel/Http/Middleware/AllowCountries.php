<?php

// @ip-info-stub-version 4.1.0

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use Symfony\Component\HttpFoundation\Response;

final class AllowCountries
{
    /**
     * @param  list<string>  $countries
     */
    public function __construct(private array $countries = []) {}

    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $this->countries !== []
            ? $this->countries
            : config('ip-info.security.allowed_countries', []);

        if (! is_array($allowed) || $allowed === []) {
            return $next($request);
        }

        $country = IpInfo::forRequest($request)->countryCode();
        $normalized = array_map(static fn (string $code): string => strtoupper($code), $allowed);

        if ($country === null || ! in_array(strtoupper($country), $normalized, true)) {
            abort(403, 'Access from your country is not allowed.');
        }

        return $next($request);
    }
}
