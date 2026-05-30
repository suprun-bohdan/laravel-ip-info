<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Laravel\IpInfoQuery;

if (! function_exists('ip_info')) {
    function ip_info(?string $ip = null): IpInfoQuery
    {
        if ($ip !== null && $ip !== '') {
            return IpInfo::for($ip);
        }

        $request = request();

        if ($request instanceof Request) {
            return IpInfo::forRequest($request);
        }

        throw new \RuntimeException('ip_info() requires a current HTTP request when no IP is provided.');
    }
}

if (! function_exists('client_ip_info')) {
    function client_ip_info(?Request $request = null): IpInfoResult
    {
        $request ??= request();

        if (! $request instanceof Request) {
            throw new \RuntimeException('client_ip_info() requires an HTTP request.');
        }

        $cached = $request->attributes->get('ip_info');

        if ($cached instanceof IpInfoResult) {
            return $cached;
        }

        return app(IpInfoManager::class)->forRequest($request)->result();
    }
}

if (! function_exists('client_country')) {
    function client_country(?Request $request = null, ?string $default = null): ?string
    {
        $country = client_ip_info($request)->countryCode();

        return $country ?? $default;
    }
}

if (! function_exists('client_ip')) {
    function client_ip(?Request $request = null): string
    {
        $request ??= request();

        if (! $request instanceof Request) {
            throw new \RuntimeException('client_ip() requires an HTTP request.');
        }

        $cached = $request->attributes->get('client_ip');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return client_ip_info($request)->ip;
    }
}
