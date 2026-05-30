<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
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
        $url = sprintf($urlTemplate, urlencode($ip->value));

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

        return ProviderResult::hit($country, $provider, new GeoLocation($country));
    }
}
