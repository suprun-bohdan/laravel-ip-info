<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

interface SchemaInspector
{
    public function hasTable(string $table): bool;
}
