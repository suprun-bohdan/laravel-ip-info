<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Database;

use Illuminate\Support\Facades\Schema;
use SuprunBohdan\IpInfo\Contracts\SchemaInspector;

final class LaravelSchemaInspector implements SchemaInspector
{
    public function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
