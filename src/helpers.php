<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Laravel\IpInfoQuery;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpPrivacyInspector;
use SuprunBohdan\IpInfo\Support\RequestProxyInspector;
use SuprunBohdan\IpInfo\Data\WhoisRecord;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;
use SuprunBohdan\IpInfo\Intel\ClientIpIntelBuilder;
use SuprunBohdan\IpInfo\Whois\WhoisLookupService;

if (! function_exists('ip_info')) {
    function ip_info(?string $ip = null): IpInfoQuery
    {
        if ($ip !== null && $ip !== '') {
            return IpInfo::for($ip);
        }

        return IpInfo::forRequest(request());
    }
}

if (! function_exists('client_ip_info')) {
    function client_ip_info(?Request $request = null): IpInfoResult
    {
        $request ??= request();

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

        $cached = $request->attributes->get('client_ip');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return client_ip_info($request)->ip;
    }
}

if (! function_exists('normalize_ip')) {
    function normalize_ip(string $ip): string
    {
        return app(IpNormalizer::class)->normalize($ip);
    }
}

if (! function_exists('ip_privacy')) {
    function ip_privacy(?string $ip = null): IpPrivacyProfile
    {
        if ($ip !== null && $ip !== '') {
            return app(IpPrivacyInspector::class)->profile($ip);
        }

        return IpInfo::forRequest(request())->privacy();
    }
}

if (! function_exists('ip_threats')) {
    function ip_threats(?string $ip = null): IpThreatSignals
    {
        if ($ip !== null && $ip !== '') {
            return IpInfo::for($ip)->threats();
        }

        return IpInfo::forRequest(request())->threats();
    }
}

if (! function_exists('request_behind_trusted_proxy')) {
    function request_behind_trusted_proxy(?Request $request = null): bool
    {
        $request ??= request();

        return app(RequestProxyInspector::class)->isBehindTrustedProxy($request);
    }
}

if (! function_exists('whois_lookup')) {
    function whois_lookup(string $ip, bool $force = false): ?WhoisRecord
    {
        return app(WhoisLookupService::class)->lookup($ip, $force);
    }
}

if (! function_exists('client_ip_intel')) {
    function client_ip_intel(?Request $request = null, bool $withWhois = false): ClientIpIntel
    {
        $request ??= request();

        $cached = $request->attributes->get('client_ip_intel');

        if ($cached instanceof ClientIpIntel) {
            return $cached;
        }

        return app(ClientIpIntelBuilder::class)->fromRequest($request, $withWhois);
    }
}
