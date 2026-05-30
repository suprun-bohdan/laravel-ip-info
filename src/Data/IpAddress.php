<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Data;

final readonly class IpAddress
{
    public function __construct(public string $value) {}

    public function toString(): string
    {
        return $this->value;
    }
}
