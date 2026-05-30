<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpHttpClient;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Support\IpValidator;
use SuprunBohdan\IpInfo\Support\UrlAllowlistGuard;

final class HttpIpProvider implements IpProvider
{
    public function __construct(
        private IpValidator $validator,
        private IpHttpClient $httpClient,
    ) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.http.enabled', false)) {
            return new ProviderResult(null, 'http', false);
        }

        if (! $this->validator->isPublic($ip->value)) {
            return new ProviderResult(null, 'http', false);
        }

        $driver = (string) config('ip-info.http.driver', 'ip-api');
        $drivers = config('ip-info.http.drivers', []);

        if (! is_array($drivers) || ! isset($drivers[$driver]) || ! is_array($drivers[$driver])) {
            return new ProviderResult(null, 'http', false);
        }

        $config = $drivers[$driver];
        $urlTemplate = (string) ($config['url'] ?? '');
        $allowedHosts = $config['allowed_hosts'] ?? [];

        if ($urlTemplate === '' || ! is_array($allowedHosts) || $allowedHosts === []) {
            return new ProviderResult(null, 'http', false);
        }

        UrlAllowlistGuard::assertAllowlisted($urlTemplate, $allowedHosts);

        $timeout = (int) config('ip-info.http.timeout', 3);

        try {
            $response = $this->httpClient->get(
                sprintf($urlTemplate, urlencode($ip->value)),
                $timeout,
            );

            if (! $response->ok()) {
                throw new ProviderException('HTTP provider request failed with HTTP '.$response->statusCode);
            }

            return $this->parseDriverResponse($driver, $response->json);
        } catch (ProviderException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ProviderException('HTTP provider error: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function parseDriverResponse(string $driver, mixed $data): ProviderResult
    {
        if (! is_array($data)) {
            return new ProviderResult(null, 'http:'.$driver, true);
        }

        return match ($driver) {
            'ip-api' => $this->parseIpApi($data, $driver),
            'ipinfo' => $this->parseIpInfo($data, $driver),
            default => new ProviderResult(null, 'http:'.$driver, true),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseIpApi(array $data, string $driver): ProviderResult
    {
        $country = $data['countryCode'] ?? null;

        if (! is_string($country) || $country === '') {
            return new ProviderResult(null, 'http:'.$driver, true);
        }

        $country = strtoupper($country);

        return new ProviderResult(
            $country,
            'http:'.$driver,
            true,
            new GeoLocation($country, is_string($data['country'] ?? null) ? $data['country'] : null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseIpInfo(array $data, string $driver): ProviderResult
    {
        $country = $data['country'] ?? null;

        if (! is_string($country) || $country === '') {
            return new ProviderResult(null, 'http:'.$driver, true);
        }

        $country = strtoupper($country);

        return new ProviderResult($country, 'http:'.$driver, true, new GeoLocation($country));
    }
}
