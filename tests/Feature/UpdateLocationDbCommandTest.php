<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\LocationDb\LocationDbCatalog;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class UpdateLocationDbCommandTest extends TestCase
{
    public function test_it_downloads_mmdb_files_via_http(): void
    {
        $storageDir = storage_path('testing/location-db-download');
        config([
            'ip-info.location_db.storage_dir' => $storageDir,
            'ip-info.location_db.edition' => 'country',
        ]);

        Http::fake([
            '*' => Http::response('mmdb-bytes', 200),
        ]);

        $exitCode = Artisan::call('ip-info:update-location-db', [
            '--edition' => 'country',
            '--force' => true,
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($catalog->filePath('country', 'ipv4'));
        $this->assertFileExists($catalog->filePath('country', 'ipv6'));
        $this->assertFileExists($catalog->metadataPath());

        @unlink($catalog->filePath('country', 'ipv4'));
        @unlink($catalog->filePath('country', 'ipv6'));
        @unlink($catalog->metadataPath());
        @rmdir($storageDir);
    }
}
