<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

final class ClientIpNotTor implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (IpInfo::forRequest(request())->isTor()) {
            $fail('Access via Tor is not allowed.');
        }
    }
}
