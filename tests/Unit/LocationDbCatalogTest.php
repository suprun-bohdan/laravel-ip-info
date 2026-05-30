<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\LocationDb\LocationDbCatalog;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class LocationDbCatalogTest extends TestCase
{
    public function test_it_resolves_country_download_urls(): void
    {
        config([
            'ip-info.location_db.source' => 'dbip',
            'ip-info.location_db.edition' => 'country',
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertStringContainsString('dbip-country-ipv4.mmdb', (string) $catalog->downloadUrl('country', 'ipv4'));
        $this->assertStringContainsString('dbip-country-ipv6.mmdb', (string) $catalog->downloadUrl('country', 'ipv6'));
    }

    public function test_it_resolves_city_download_urls(): void
    {
        config([
            'ip-info.location_db.source' => 'dbip',
            'ip-info.location_db.edition' => 'city',
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertStringContainsString('dbip-city-ipv4.mmdb', (string) $catalog->downloadUrl('city', 'ipv4'));
        $this->assertStringContainsString('dbip-city-ipv6.mmdb', (string) $catalog->downloadUrl('city', 'ipv6'));
    }

    public function test_file_paths_include_edition_and_ip_version(): void
    {
        $storageDir = storage_path('testing/location-db');
        config(['ip-info.location_db.storage_dir' => $storageDir]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertSame(
            $storageDir.DIRECTORY_SEPARATOR.'city-ipv6.mmdb',
            $catalog->filePath('city', 'ipv6'),
        );
    }

    public function test_is_installed_requires_both_ip_versions(): void
    {
        $storageDir = sys_get_temp_dir().'/ip-info-location-db-'.uniqid('', true);
        mkdir($storageDir, 0755, true);

        config(['ip-info.location_db.storage_dir' => $storageDir]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        file_put_contents($catalog->filePath('country', 'ipv4'), 'v4');

        $this->assertFalse($catalog->isInstalled('country'));

        file_put_contents($catalog->filePath('country', 'ipv6'), 'v6');

        $this->assertTrue($catalog->isInstalled('country'));

        @unlink($catalog->filePath('country', 'ipv4'));
        @unlink($catalog->filePath('country', 'ipv6'));
        @rmdir($storageDir);
    }
}
