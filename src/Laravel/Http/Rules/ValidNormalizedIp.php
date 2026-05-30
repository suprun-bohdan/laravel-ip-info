<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class ValidNormalizedIp implements ValidationRule
{
    public function __construct(
        private ?IpNormalizer $normalizer = null,
        private ?IpValidator $validator = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid IP address.');

            return;
        }

        $normalizer = $this->normalizer ?? new IpNormalizer;
        $validator = $this->validator ?? new IpValidator;

        try {
            $normalized = $normalizer->normalize($value);
        } catch (\Throwable) {
            $fail('The :attribute must be a valid IP address.');

            return;
        }

        if (! $validator->isValid($normalized)) {
            $fail('The :attribute must be a valid IP address.');
        }
    }
}
