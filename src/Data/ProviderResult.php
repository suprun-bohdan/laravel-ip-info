<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

use SuprunBohdan\IpInfo\Contracts\IpProvider;

/**
 * Result returned by a single {@see IpProvider}.
 *
 * - `$resolved === true` — provider handled the lookup; `$countryCode` may still be null.
 * - `$resolved === false` — provider skipped or could not resolve; chain continues.
 */
final readonly class ProviderResult
{
    public function __construct(
        public ?string $countryCode,
        public string $provider,
        public bool $resolved,
    ) {}
}
