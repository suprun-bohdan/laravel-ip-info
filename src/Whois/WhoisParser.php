<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Whois;

use SuprunBohdan\IpInfo\Data\WhoisRecord;

final class WhoisParser
{
    public function parse(string $ip, string $raw, ?string $registry = null): WhoisRecord
    {
        $objects = $this->parseObjects($raw);
        $inetObject = $this->firstObjectWithKey($objects, 'inetnum')
            ?? $this->firstObjectWithKey($objects, 'inet6num');
        $routeObject = $this->firstObjectWithKey($objects, 'route')
            ?? $this->firstObjectWithKey($objects, 'route6');
        $roleObject = $this->firstObjectWithKey($objects, 'abuse-mailbox');

        $inetnum = $this->firstValue($inetObject, 'inetnum')
            ?? $this->firstValue($inetObject, 'inet6num');
        $netname = $this->firstValue($inetObject, 'netname');
        $country = $this->firstValue($inetObject, 'country');
        $status = $this->firstValue($inetObject, 'status');
        $organization = $this->firstValue($inetObject, 'org')
            ?? $this->firstValue($inetObject, 'organisation');
        $descriptions = $this->values($inetObject, 'descr');
        $route = $this->firstValue($routeObject, 'route')
            ?? $this->firstValue($routeObject, 'route6');
        $origin = $this->firstValue($routeObject, 'origin');
        $abuseEmail = $this->firstValue($inetObject, 'abuse-mailbox')
            ?? $this->firstValue($roleObject, 'abuse-mailbox');

        if ($origin !== null) {
            $origin = strtoupper(trim($origin));

            if (preg_match('/^AS(\d+)$/', $origin, $matches) === 1) {
                $origin = 'AS'.$matches[1];
            } elseif (preg_match('/^\d+$/', $origin) === 1) {
                $origin = 'AS'.$origin;
            }
        }

        return new WhoisRecord(
            ip: $ip,
            inetnum: $inetnum,
            netname: $netname,
            descriptions: $descriptions,
            country: $country !== null ? strtoupper($country) : null,
            organization: $organization,
            abuseEmail: $abuseEmail,
            status: $status,
            route: $route,
            originAsn: $origin,
            source: $this->firstValue($inetObject, 'source'),
            registry: $registry,
        );
    }

    public function referralServer(string $raw): ?string
    {
        if (preg_match('/^refer:\s*(\S+)/mi', $raw, $matches) === 1) {
            return strtolower($matches[1]);
        }

        if (preg_match('/^ReferralServer:\s*r?whois:\/\/(.+)$/mi', $raw, $matches) === 1) {
            return strtolower(trim($matches[1]));
        }

        return null;
    }

    /**
     * @return list<array<string, list<string>>>
     */
    private function parseObjects(string $raw): array
    {
        $objects = [];
        $current = [];

        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $line = rtrim($line);

            if ($line === '' || str_starts_with($line, '%')) {
                if ($current !== []) {
                    $objects[] = $current;
                    $current = [];
                }

                continue;
            }

            if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches) !== 1) {
                continue;
            }

            $key = strtolower(trim($matches[1]));
            $value = trim($matches[2]);

            if ($value === '') {
                continue;
            }

            $current[$key][] = $value;
        }

        if ($current !== []) {
            $objects[] = $current;
        }

        return $objects;
    }

    /**
     * @param  list<array<string, list<string>>>  $objects
     * @return array<string, list<string>>|null
     */
    private function firstObjectWithKey(array $objects, string $key): ?array
    {
        foreach ($objects as $object) {
            if (isset($object[$key])) {
                return $object;
            }
        }

        return null;
    }

    /**
     * @param  array<string, list<string>>|null  $object
     */
    private function firstValue(?array $object, string $key): ?string
    {
        if ($object === null || ! isset($object[$key][0])) {
            return null;
        }

        return $object[$key][0];
    }

    /**
     * @param  array<string, list<string>>|null  $object
     * @return list<string>
     */
    private function values(?array $object, string $key): array
    {
        if ($object === null || ! isset($object[$key])) {
            return [];
        }

        return array_values(array_filter($object[$key], static fn (string $value): bool => $value !== ''));
    }
}
