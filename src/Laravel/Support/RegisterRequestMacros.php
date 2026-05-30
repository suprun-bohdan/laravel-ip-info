<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Support;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpPrivacyProfile;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Support\RequestProxyInspector;

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

        Request::macro('clientCity', function (?string $default = null): ?string {
            /** @var Request $this */
            return client_city($this, $default);
        });

        Request::macro('clientIp', function (): string {
            /** @var Request $this */
            return client_ip($this);
        });

        Request::macro('isCountry', function (string ...$codes): bool {
            /** @var Request $this */
            return client_ip_info($this)->isCountry(...$codes);
        });

        Request::macro('normalizedClientIp', function (): string {
            /** @var Request $this */
            return IpInfo::forRequest($this)->normalizedIp();
        });

        Request::macro('clientIpPrivacy', function (): IpPrivacyProfile {
            /** @var Request $this */
            return IpInfo::forRequest($this)->privacy();
        });

        Request::macro('clientIpThreats', function (): IpThreatSignals {
            /** @var Request $this */
            return IpInfo::forRequest($this)->threats();
        });

        Request::macro('isTorClient', function (): bool {
            /** @var Request $this */
            return IpInfo::forRequest($this)->isTor();
        });

        Request::macro('isProxyClient', function (): bool {
            /** @var Request $this */
            return IpInfo::forRequest($this)->isProxy();
        });

        Request::macro('isBehindTrustedProxy', function (): bool {
            /** @var Request $this */
            return app(RequestProxyInspector::class)->isBehindTrustedProxy($this);
        });

        Request::macro('clientIpIntel', function (bool $withWhois = false): ClientIpIntel {
            /** @var Request $this */
            return client_ip_intel($this, $withWhois);
        });

        Request::macro('clientIpRisk', function (): \SuprunBohdan\IpInfo\Intel\ClientIpRiskScore {
            /** @var Request $this */
            return client_ip_risk($this);
        });

        Request::macro('isVerifiedCrawler', function (): bool {
            /** @var Request $this */
            return is_verified_crawler(client_ip($this));
        });
    }
}
