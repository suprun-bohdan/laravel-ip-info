<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Support;

use SuprunBohdan\IpInfo\Data\IpThreatSignals;

final class IpThreatInspector
{
    public function __construct(private IpValidator $validator) {}

    public function inspect(string $ip): IpThreatSignals
    {
        if (! (bool) config('ip-info.threat_intel.enabled', true)) {
            return IpThreatSignals::unknown('disabled');
        }

        if (! $this->validator->isValid($ip) || ! $this->validator->isPublic($ip)) {
            return IpThreatSignals::unknown('non-public');
        }

        return new IpThreatSignals(
            tor: $this->matchConfiguredCidrs($ip, 'tor_exit_cidrs'),
            proxy: $this->matchConfiguredCidrs($ip, 'known_proxy_cidrs'),
            vpn: null,
            hosting: $this->matchConfiguredCidrs($ip, 'hosting_cidrs'),
            source: 'local',
        );
    }

    private function matchConfiguredCidrs(string $ip, string $key): ?bool
    {
        $cidrs = config('ip-info.threat_intel.'.$key, []);

        if (! is_array($cidrs) || $cidrs === []) {
            return null;
        }

        return CidrMatcher::matchesAny($ip, $cidrs);
    }
}
