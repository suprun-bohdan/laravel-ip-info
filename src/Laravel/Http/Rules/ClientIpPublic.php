<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class ClientIpPublic implements ValidationRule
{
    public function __construct(private ?IpValidator $validator = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid public IP address.');

            return;
        }

        $validator = $this->validator ?? new IpValidator;

        if (! $validator->isValid($value) || ! $validator->isPublic($value)) {
            $fail('The :attribute must be a valid public IP address.');
        }
    }
}
