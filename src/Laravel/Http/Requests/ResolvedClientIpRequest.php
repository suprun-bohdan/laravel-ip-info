<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;

abstract class ResolvedClientIpRequest extends FormRequest
{
    public function clientIp(): string
    {
        $cached = $this->attributes->get('client_ip');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->ipInfo()->ip;
    }

    public function ipInfo(): IpInfoResult
    {
        $cached = $this->attributes->get('ip_info');

        if ($cached instanceof IpInfoResult) {
            return $cached;
        }

        return app(IpInfoManager::class)->forRequest($this)->result();
    }
}
