<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Support;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;

final class RegisterRequestMacros
{
    public static function register(): void
    {
        if (Request::hasMacro('ipInfo')) {
            return;
        }

        Request::macro('ipInfo', function (): IpInfoResult {
            /** @var Request $this */
            $cached = $this->attributes->get('ip_info');

            if ($cached instanceof IpInfoResult) {
                return $cached;
            }

            return app(IpInfoManager::class)->forRequest($this)->result();
        });

        Request::macro('clientCountry', function (?string $default = null): ?string {
            /** @var Request $this */
            $country = $this->ipInfo()->countryCode();

            return $country ?? $default;
        });

        Request::macro('clientIp', function (): string {
            /** @var Request $this */
            $cached = $this->attributes->get('client_ip');

            if (is_string($cached) && $cached !== '') {
                return $cached;
            }

            return $this->ipInfo()->ip;
        });

        Request::macro('isCountry', function (string ...$codes): bool {
            /** @var Request $this */
            return $this->ipInfo()->isCountry(...$codes);
        });
    }
}
