<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

final class CountryIn implements ValidationRule
{
    /**
     * @param  list<string>  $countries
     */
    public function __construct(private array $countries) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid IP address.');

            return;
        }

        $country = IpInfo::for($value)->countryCode();
        $allowed = array_map(static fn (string $code): string => strtoupper($code), $this->countries);

        if ($country === null || ! in_array(strtoupper($country), $allowed, true)) {
            $fail('The :attribute country is not allowed.');
        }
    }
}
