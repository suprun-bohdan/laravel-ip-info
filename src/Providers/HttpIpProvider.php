<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Http\ResilientHttpExecutor;
use SuprunBohdan\IpInfo\Support\IpValidator;
use SuprunBohdan\IpInfo\Support\UrlAllowlistGuard;

final class HttpIpProvider implements IpProvider
{
    public function __construct(
        private IpValidator $validator,
        private ResilientHttpExecutor $http,
    ) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.http.enabled', false)) {
            return ProviderResult::skipped('http');
        }

        if (! $this->validator->isPublic($ip->value)) {
            return ProviderResult::skipped('http');
        }

        $driver = (string) config('ip-info.http.driver', 'ipinfo');
        $drivers = config('ip-info.http.drivers', []);

        if (! is_array($drivers) || ! isset($drivers[$driver]) || ! is_array($drivers[$driver])) {
            return ProviderResult::skipped('http');
        }

        $config = $drivers[$driver];
        $urlTemplate = (string) ($config['url'] ?? '');
        $allowedHosts = $config['allowed_hosts'] ?? [];
        $allowInsecure = (bool) config('ip-info.http.allow_insecure', false);

        if ($urlTemplate === '' || ! is_array($allowedHosts) || $allowedHosts === []) {
            return ProviderResult::skipped('http');
        }

        UrlAllowlistGuard::assertAllowlisted($urlTemplate, $allowedHosts, $allowInsecure);

        $timeout = (int) config('ip-info.http.timeout', 3);
        $url = $this->buildRequestUrl($driver, $urlTemplate, $ip->value);

        try {
            $result = $this->http->get('http:'.$driver, $url, $timeout);

            if ($result['soft_fail'] || $result['response'] === null) {
                return ProviderResult::failed('http:'.$driver, 'HTTP soft-fail or empty response.');
            }

            return $this->parseDriverResponse($driver, $result['response']->json);
        } catch (ProviderException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ProviderException('HTTP provider error: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function buildRequestUrl(string $driver, string $urlTemplate, string $ip): string
    {
        $url = sprintf($urlTemplate, urlencode($ip));

        if (! (bool) config('ip-info.http.enrich_threat_signals', true)) {
            return $url;
        }

        return match ($driver) {
            'ip-api' => $this->appendQueryFields($url, 'status,country,countryCode,proxy,hosting'),
            'ipinfo' => $url,
            default => $url,
        };
    }

    private function appendQueryFields(string $url, string $fields): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        if (preg_match('/(?:^|[?&])fields=/', $url) === 1) {
            return $url;
        }

        return $url.$separator.'fields='.$fields;
    }

    private function parseDriverResponse(string $driver, mixed $data): ProviderResult
    {
        $provider = 'http:'.$driver;

        if (! is_array($data)) {
            return ProviderResult::miss($provider);
        }

        return match ($driver) {
            'ip-api' => $this->parseIpApi($data, $provider),
            'ipinfo' => $this->parseIpInfo($data, $provider),
            default => ProviderResult::miss($provider),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseIpApi(array $data, string $provider): ProviderResult
    {
        $country = $data['countryCode'] ?? null;

        if (! is_string($country) || $country === '') {
            return ProviderResult::miss($provider);
        }

        $country = strtoupper($country);

        return ProviderResult::hit(
            $country,
            $provider,
            new GeoLocation($country, is_string($data['country'] ?? null) ? $data['country'] : null),
            threats: $this->threatSignalsFromIpApi($data, $provider),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseIpInfo(array $data, string $provider): ProviderResult
    {
        $country = $data['country'] ?? null;

        if (! is_string($country) || $country === '') {
            return ProviderResult::miss($provider);
        }

        $country = strtoupper($country);

        return ProviderResult::hit(
            $country,
            $provider,
            new GeoLocation($country),
            threats: $this->threatSignalsFromIpInfo($data, $provider),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function threatSignalsFromIpApi(array $data, string $provider): ?IpThreatSignals
    {
        if (! (bool) config('ip-info.http.enrich_threat_signals', true)) {
            return null;
        }

        $proxy = array_key_exists('proxy', $data) ? (bool) $data['proxy'] : null;
        $hosting = array_key_exists('hosting', $data) ? (bool) $data['hosting'] : null;

        if ($proxy === null && $hosting === null) {
            return null;
        }

        return new IpThreatSignals(
            proxy: $proxy,
            hosting: $hosting,
            source: $provider,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function threatSignalsFromIpInfo(array $data, string $provider): ?IpThreatSignals
    {
        if (! (bool) config('ip-info.http.enrich_threat_signals', true)) {
            return null;
        }

        $privacy = $data['privacy'] ?? null;

        if (! is_array($privacy)) {
            return null;
        }

        return new IpThreatSignals(
            tor: array_key_exists('tor', $privacy) ? (bool) $privacy['tor'] : null,
            proxy: array_key_exists('proxy', $privacy) ? (bool) $privacy['proxy'] : null,
            vpn: array_key_exists('vpn', $privacy) ? (bool) $privacy['vpn'] : null,
            hosting: array_key_exists('hosting', $privacy) ? (bool) $privacy['hosting'] : null,
            source: $provider,
        );
    }
}
