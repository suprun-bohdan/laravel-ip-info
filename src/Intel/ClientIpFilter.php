<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Intel;

final class ClientIpFilter
{
    public function __construct(
        private VerifiedCrawlerInspector $crawlerInspector,
    ) {}

    public function blockReason(ClientIpIntel $intel): ?string
    {
        if (! (bool) config('ip-info.filtering.enabled', false)) {
            return null;
        }

        if (
            (bool) config('ip-info.verified_crawlers.skip_filtering', true)
            && $this->crawlerInspector->isVerified($intel->geo->ip)
        ) {
            return null;
        }

        $threats = $intel->threats();

        if ((bool) config('ip-info.filtering.block_tor', false) && ($threats?->isTor() ?? false)) {
            return 'tor';
        }

        if ((bool) config('ip-info.filtering.block_proxy', false) && ($threats?->isProxy() ?? false)) {
            return 'proxy';
        }

        if ((bool) config('ip-info.filtering.block_vpn', false) && ($threats?->isVpn() ?? false)) {
            return 'vpn';
        }

        if ((bool) config('ip-info.filtering.block_hosting', false) && ($threats?->isHosting() ?? false)) {
            return 'hosting';
        }

        $country = $intel->geo->countryCode();

        if ($country !== null) {
            $blockedCountries = config('ip-info.filtering.blocked_countries', []);

            if (is_array($blockedCountries) && $blockedCountries !== []) {
                $normalized = strtoupper($country);

                foreach ($blockedCountries as $blocked) {
                    if (is_string($blocked) && strtoupper($blocked) === $normalized) {
                        return 'country';
                    }
                }
            }
        }

        if ($intel->whois !== null) {
            foreach ($this->stringList('blocked_netnames') as $pattern) {
                if ($intel->whois->matchesNetname($pattern)) {
                    return 'whois_netname';
                }
            }

            foreach ($this->stringList('blocked_organizations') as $needle) {
                if ($intel->whois->matchesOrganization($needle)) {
                    return 'whois_organization';
                }
            }

            if ($intel->whois->originAsn !== null) {
                foreach ($this->stringList('blocked_origin_asns') as $blockedAsn) {
                    if (strtoupper($blockedAsn) === strtoupper($intel->whois->originAsn)) {
                        return 'whois_asn';
                    }
                }
            }

            if ((bool) config('ip-info.filtering.require_whois_country_match', false)) {
                $geoCountry = $intel->geo->countryCode();
                $whoisCountry = $intel->whois->country;

                if ($geoCountry !== null && $whoisCountry !== null && strtoupper($geoCountry) !== strtoupper($whoisCountry)) {
                    return 'whois_country_mismatch';
                }
            }
        }

        return null;
    }

    public function shouldBlock(ClientIpIntel $intel): bool
    {
        return $this->blockReason($intel) !== null;
    }

    /**
     * @return list<string>
     */
    private function stringList(string $key): array
    {
        $values = config('ip-info.filtering.'.$key, []);

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => is_string($value) ? trim($value) : '',
            $values,
        )));
    }
}
