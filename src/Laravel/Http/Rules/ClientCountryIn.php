<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

final class ClientCountryIn implements ValidationRule
{
    /**
     * @param  list<string>  $countries
     */
    public function __construct(
        private array $countries,
        private ?Request $request = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        unset($attribute, $value);

        $request = $this->request ?? request();

        if (! $request instanceof Request) {
            $fail('Unable to resolve client country.');

            return;
        }

        $country = IpInfo::forRequest($request)->countryCode();
        $allowed = array_map(static fn (string $code): string => strtoupper($code), $this->countries);

        if ($country === null || ! in_array(strtoupper($country), $allowed, true)) {
            $fail('Access from your country is not allowed.');
        }
    }
}
