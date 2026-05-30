<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Providers;

use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Http\ResilientHttpExecutor;
use SuprunBohdan\IpInfo\Support\IpValidator;
use SuprunBohdan\IpInfo\Support\UrlAllowlistGuard;

final class CleanTalkProvider implements IpProvider
{
    private const ALLOWED_HOST = 'api.cleantalk.org';

    public function __construct(
        private IpValidator $validator,
        private ResilientHttpExecutor $http,
    ) {}

    public function lookup(IpAddress $ip): ProviderResult
    {
        if (! config('ip-info.cleantalk.enabled', false)) {
            return ProviderResult::skipped('cleantalk');
        }

        if (! $this->validator->isPublic($ip->value)) {
            return ProviderResult::skipped('cleantalk');
        }

        $timeout = (int) config('ip-info.cleantalk.timeout', 3);
        $urlTemplate = (string) config(
            'ip-info.cleantalk.url',
            'https://api.cleantalk.org/?method_name=ip_info&ip=%s'
        );

        UrlAllowlistGuard::assertAllowlisted($urlTemplate, [self::ALLOWED_HOST]);

        $url = sprintf($urlTemplate, urlencode($ip->value));

        try {
            $result = $this->http->get('cleantalk', $url, $timeout, 'ip-info.cleantalk');

            if ($result['soft_fail'] || $result['response'] === null) {
                return ProviderResult::failed('cleantalk', 'HTTP soft-fail or empty response.');
            }

            $data = $result['response']->json ?? [];
            $country = $data['data'][$ip->value]['country_code'] ?? null;

            if (! is_string($country) || $country === '') {
                return ProviderResult::miss('cleantalk');
            }

            return ProviderResult::hit(strtoupper($country), 'cleantalk');
        } catch (ProviderException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ProviderException('CleanTalk provider error: '.$exception->getMessage(), 0, $exception);
        }
    }
}
