<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

final class ClientIpNotProxy implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = IpInfo::forRequest(request());

        if ($query->isProxy() || $query->isVpn()) {
            $fail('Access via proxy or VPN is not allowed.');
        }
    }
}
