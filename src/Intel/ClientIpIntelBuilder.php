<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Intel;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use SuprunBohdan\IpInfo\Whois\WhoisLookupService;

final class ClientIpIntelBuilder
{
    public function __construct(
        private IpInfoManager $ipInfo,
        private WhoisLookupService $whois,
    ) {}

    public function fromRequest(Request $request, ?bool $withWhois = null): ClientIpIntel
    {
        $query = $this->ipInfo->forRequest($request);

        return $this->fromResult(
            $query->result(),
            $query->privacy(),
            $withWhois,
        );
    }

    public function fromIp(string $ip, ?bool $withWhois = null): ClientIpIntel
    {
        $query = $this->ipInfo->for($ip);

        return $this->fromResult(
            $query->result(),
            $query->privacy(),
            $withWhois,
        );
    }

    public function fromResult(
        IpInfoResult $result,
        ?IpPrivacyProfile $privacy = null,
        ?bool $withWhois = null,
    ): ClientIpIntel {
        $withWhois ??= false;

        return new ClientIpIntel(
            geo: $result,
            privacy: $privacy ?? $this->ipInfo->privacyProfile(new IpAddress($result->ip)),
            whois: $withWhois ? $this->whois->lookup($result->ip) : null,
        );
    }

    public function filterNeedsWhois(): bool
    {
        if (! (bool) config('ip-info.whois.enabled', false)) {
            return false;
        }

        if (! (bool) config('ip-info.filtering.enabled', false)) {
            return false;
        }

        foreach (['blocked_netnames', 'blocked_organizations', 'blocked_origin_asns'] as $key) {
            $values = config('ip-info.filtering.'.$key, []);

            if (is_array($values) && $values !== []) {
                return true;
            }
        }

        return (bool) config('ip-info.filtering.require_whois_country_match', false);
    }
}
