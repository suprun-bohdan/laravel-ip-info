<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

final class CountryNotIn implements ValidationRule
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

        if ($country === null) {
            return;
        }

        $blocked = array_map(static fn (string $code): string => strtoupper($code), $this->countries);

        if (in_array(strtoupper($country), $blocked, true)) {
            $fail('The :attribute country is not allowed.');
        }
    }
}
