<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Privacy;

use SuprunBohdan\IpInfo\Data\IpInfoResult;

final class IpPrivacyPolicy
{
    public function shouldLog(IpInfoResult $result): bool
    {
        if ($result->isPrivate && $this->skipPrivateIps()) {
            return false;
        }

        return $this->logLookupsEnabled();
    }

    public function forLogging(IpInfoResult $result): IpInfoResult
    {
        return $result->forLogging();
    }

    private function skipPrivateIps(): bool
    {
        return (bool) config('ip-info.privacy.skip_private_ips', true);
    }

    private function logLookupsEnabled(): bool
    {
        return (bool) config('ip-info.privacy.log_lookups', true);
    }
}
