<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use JsonSerializable;

final readonly class WhoisRecord implements JsonSerializable
{
    /**
     * @param  list<string>  $descriptions
     */
    public function __construct(
        public string $ip,
        public ?string $inetnum,
        public ?string $netname,
        public array $descriptions,
        public ?string $country,
        public ?string $organization,
        public ?string $abuseEmail,
        public ?string $status,
        public ?string $route,
        public ?string $originAsn,
        public ?string $source,
        public ?string $registry,
    ) {}

    public function organizationLabel(): ?string
    {
        if ($this->organization !== null && $this->organization !== '') {
            return $this->organization;
        }

        return $this->descriptions[0] ?? $this->netname;
    }

    public function matchesNetname(string $pattern): bool
    {
        if ($this->netname === null || $this->netname === '') {
            return false;
        }

        return fnmatch(strtoupper($pattern), strtoupper($this->netname));
    }

    public function matchesOrganization(string $needle): bool
    {
        $haystack = strtoupper(implode(' ', array_filter([
            $this->organization,
            $this->netname,
            ...$this->descriptions,
        ])));

        return str_contains($haystack, strtoupper($needle));
    }

    /**
     * @return array{
     *     ip: string,
     *     inetnum: ?string,
     *     netname: ?string,
     *     descriptions: list<string>,
     *     country: ?string,
     *     organization: ?string,
     *     abuse_email: ?string,
     *     status: ?string,
     *     route: ?string,
     *     origin_asn: ?string,
     *     source: ?string,
     *     registry: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'inetnum' => $this->inetnum,
            'netname' => $this->netname,
            'descriptions' => $this->descriptions,
            'country' => $this->country,
            'organization' => $this->organization,
            'abuse_email' => $this->abuseEmail,
            'status' => $this->status,
            'route' => $this->route,
            'origin_asn' => $this->originAsn,
            'source' => $this->source,
            'registry' => $this->registry,
        ];
    }

    /**
     * @return array{
     *     ip: string,
     *     inetnum: ?string,
     *     netname: ?string,
     *     descriptions: list<string>,
     *     country: ?string,
     *     organization: ?string,
     *     abuse_email: ?string,
     *     status: ?string,
     *     route: ?string,
     *     origin_asn: ?string,
     *     source: ?string,
     *     registry: ?string
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
