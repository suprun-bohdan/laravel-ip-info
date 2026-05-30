<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Support;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Data\IpInfoResult;

final class RegisterRequestMacros
{
    public static function register(): void
    {
        if (Request::hasMacro('ipInfo')) {
            return;
        }

        Request::macro('ipInfo', function (): IpInfoResult {
            /** @var Request $this */
            return client_ip_info($this);
        });

        Request::macro('clientCountry', function (?string $default = null): ?string {
            /** @var Request $this */
            return client_country($this, $default);
        });

        Request::macro('clientIp', function (): string {
            /** @var Request $this */
            return client_ip($this);
        });

        Request::macro('isCountry', function (string ...$codes): bool {
            /** @var Request $this */
            return client_ip_info($this)->isCountry(...$codes);
        });
    }
}
