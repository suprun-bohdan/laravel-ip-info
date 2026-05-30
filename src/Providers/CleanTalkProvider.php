<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class CleanTalkProvider implements IpProvider
{
    private const ALLOWED_HOST = 'api.cleantalk.org';

    public function __construct(private IpValidator $validator) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.cleantalk.enabled', false)) {
            return new ProviderResult(null, 'cleantalk', false);
        }

        if (! $this->validator->isPublic($ip->value)) {
            return new ProviderResult(null, 'cleantalk', false);
        }

        $timeout = (int) config('ip-info.cleantalk.timeout', 3);
        $urlTemplate = (string) config(
            'ip-info.cleantalk.url',
            'https://api.cleantalk.org/?method_name=ip_info&ip=%s'
        );

        $this->assertAllowlistedUrl($urlTemplate);

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['allow_redirects' => false])
                ->get(sprintf($urlTemplate, urlencode($ip->value)));

            if (! $response->ok()) {
                throw new ProviderException('CleanTalk request failed with HTTP '.$response->status());
            }

            $data = $response->json();
            $country = $data['data'][$ip->value]['country_code'] ?? null;

            if (! is_string($country) || $country === '') {
                return new ProviderResult(null, 'cleantalk', false);
            }

            return new ProviderResult(strtoupper($country), 'cleantalk', true);
        } catch (ProviderException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ProviderException('CleanTalk provider error: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function assertAllowlistedUrl(string $urlTemplate): void
    {
        $host = parse_url($urlTemplate, PHP_URL_HOST);

        if (! is_string($host) || strtolower($host) !== self::ALLOWED_HOST) {
            throw new ProviderException(
                'CleanTalk URL host must be '.self::ALLOWED_HOST.', got '.($host ?? 'none').'.'
            );
        }
    }
}
