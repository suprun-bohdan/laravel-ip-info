<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Http;

final readonly class IpHttpResponse
{
    /**
     * @param  array<string, mixed>|null  $json
     */
    public function __construct(
        public int $statusCode,
        public ?array $json,
    ) {}

    public function ok(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
