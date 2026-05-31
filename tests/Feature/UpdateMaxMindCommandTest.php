<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class UpdateMaxMindCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\PharData::class)) {
            $this->markTestSkipped('ext-phar is required for MaxMind archive extraction.');
        }
    }

    public function test_it_downloads_country_edition_archive(): void
    {
        $archive = $this->createMaxMindArchive('GeoLite2-Country.mmdb', 'country-bytes');

        Http::fake([
            '*' => Http::response($archive, 200),
        ]);

        config([
            'ip-info.maxmind.license_key' => 'test-license',
            'ip-info.maxmind.database_path' => null,
            'ip-info.maxmind.edition' => 'country',
        ]);

        $exitCode = Artisan::call('ip-info:update-maxmind', [
            '--edition' => 'country',
            '--force' => true,
        ]);

        $path = storage_path('app/private/geoip/GeoLite2-Country.mmdb');

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($path);
        $this->assertSame('country-bytes', file_get_contents($path));

        @unlink($path);
        @rmdir(dirname($path));
    }

    public function test_it_downloads_city_edition_when_option_provided(): void
    {
        $archive = $this->createMaxMindArchive('GeoLite2-City.mmdb', 'city-bytes');

        Http::fake([
            '*' => Http::response($archive, 200),
        ]);

        config([
            'ip-info.maxmind.license_key' => 'test-license',
            'ip-info.maxmind.database_path' => null,
        ]);

        $exitCode = Artisan::call('ip-info:update-maxmind', [
            '--edition' => 'city',
            '--force' => true,
        ]);

        $path = storage_path('app/private/geoip/GeoLite2-City.mmdb');

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($path);
        $this->assertSame('city-bytes', file_get_contents($path));

        @unlink($path);
        @rmdir(dirname($path));
    }

    private function createMaxMindArchive(string $filename, string $contents): string
    {
        $root = sys_get_temp_dir().'/maxmind-test-'.uniqid('', true);
        $bundle = $root.'/bundle';
        mkdir($bundle, 0777, true);
        file_put_contents($bundle.'/'.$filename, $contents);

        $tarPath = $root.'/download.tar';
        $tar = new \PharData($tarPath);
        $tar->addFile($bundle.'/'.$filename, $filename);
        unset($tar);

        $tarGzPath = $tarPath.'.gz';
        (new \PharData($tarPath))->compress(\Phar::GZ);

        $bytes = file_get_contents($tarGzPath);

        @unlink($tarGzPath);
        @unlink($tarPath);
        $this->deleteDirectory($root);

        return is_string($bytes) ? $bytes : '';
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
