<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\MaxMind\MaxMindCatalog;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class MaxMindCatalogTest extends TestCase
{
    public function test_it_resolves_edition_specific_database_path(): void
    {
        config([
            'ip-info.maxmind.database_path' => null,
            'ip-info.maxmind.edition' => 'city',
        ]);

        $catalog = $this->app->make(MaxMindCatalog::class);

        $this->assertSame('city', $catalog->edition());
        $this->assertSame('GeoLite2-City', $catalog->editionId());
        $this->assertStringEndsWith('GeoLite2-City.mmdb', $catalog->databasePath());
    }

    public function test_it_uses_explicit_database_path_when_configured(): void
    {
        $path = storage_path('testing/custom.mmdb');
        config(['ip-info.maxmind.database_path' => $path]);

        $catalog = $this->app->make(MaxMindCatalog::class);

        $this->assertSame($path, $catalog->databasePath());
    }
}
