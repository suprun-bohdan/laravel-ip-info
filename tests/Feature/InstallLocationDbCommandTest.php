<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\LocationDb\LocationDbCatalog;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class InstallLocationDbCommandTest extends TestCase
{
    private string $storageDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageDir = storage_path('testing/install-location-db-'.uniqid('', true));
        config(['ip-info.location_db.storage_dir' => $this->storageDir]);

        Http::fake([
            '*' => Http::response('mmdb-bytes', 200),
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['country', 'city', 'asn_country', 'asn'] as $edition) {
            foreach (['ipv4', 'ipv6'] as $version) {
                @unlink($this->storageDir.DIRECTORY_SEPARATOR.$edition.'-'.$version.'.mmdb');
            }
        }

        @unlink($this->storageDir.DIRECTORY_SEPARATOR.'metadata.json');
        @rmdir($this->storageDir);

        parent::tearDown();
    }

    public function test_install_with_location_db_downloads_country_edition(): void
    {
        $exitCode = Artisan::call('ip-info:install', [
            '--with-location-db' => true,
            '--force' => true,
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($catalog->isInstalled('country'));
        $this->assertTrue(config('ip-info.location_db.enabled'));
        $this->assertSame('country', config('ip-info.location_db.edition'));
    }

    public function test_install_with_location_db_city_downloads_city_edition(): void
    {
        $exitCode = Artisan::call('ip-info:install', [
            '--with-location-db' => 'city',
            '--force' => true,
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($catalog->isInstalled('city'));
        $this->assertSame('city', config('ip-info.location_db.edition'));
    }

    public function test_install_with_asn_country_edition(): void
    {
        $exitCode = Artisan::call('ip-info:install', [
            '--with-location-db' => 'asn_country',
            '--force' => true,
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($catalog->isInstalled('asn_country'));
        $this->assertSame('asn_country', config('ip-info.location_db.edition'));
        $this->assertSame('routeviews', config('ip-info.location_db.source'));
    }

    public function test_install_with_asn_db_downloads_enrichment_files(): void
    {
        $exitCode = Artisan::call('ip-info:install', [
            '--with-asn-db' => true,
            '--force' => true,
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($catalog->isInstalled('asn'));
        $this->assertTrue(config('ip-info.location_db.enrich_asn'));
    }

    public function test_install_rejects_invalid_location_db_edition(): void
    {
        $exitCode = Artisan::call('ip-info:install', [
            '--with-location-db' => 'invalid',
            '--force' => true,
        ]);

        $this->assertSame(1, $exitCode);
    }
}
