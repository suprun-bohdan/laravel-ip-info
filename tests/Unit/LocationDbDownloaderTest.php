<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Support\Facades\Http;
use SuprunBohdan\IpInfo\LocationDb\LocationDbCatalog;
use SuprunBohdan\IpInfo\LocationDb\LocationDbDownloader;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class LocationDbDownloaderTest extends TestCase
{
    private string $storageDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageDir = storage_path('testing/location-db-etag-'.uniqid('', true));
        config([
            'ip-info.location_db.storage_dir' => $this->storageDir,
            'ip-info.location_db.edition' => 'country',
        ]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->storageDir);
        parent::tearDown();
    }

    public function test_it_returns_not_modified_on_http_304(): void
    {
        $catalog = $this->app->make(LocationDbCatalog::class);
        $ipv4 = $catalog->filePath('country', 'ipv4');
        $ipv6 = $catalog->filePath('country', 'ipv6');

        mkdir(dirname($ipv4), 0777, true);
        file_put_contents($ipv4, 'existing-ipv4');
        file_put_contents($ipv6, 'existing-ipv6');

        file_put_contents($catalog->metadataPath(), json_encode([
            'files' => [
                [
                    'edition' => 'country',
                    'ip_version' => 'ipv4',
                    'path' => $ipv4,
                    'url' => 'https://cdn.example/country-ipv4.mmdb',
                    'etag' => '"etag-v4"',
                ],
                [
                    'edition' => 'country',
                    'ip_version' => 'ipv6',
                    'path' => $ipv6,
                    'url' => 'https://cdn.example/country-ipv6.mmdb',
                    'etag' => '"etag-v6"',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        Http::fake([
            'https://cdn.example/country-ipv4.mmdb' => Http::response('', 304, ['ETag' => '"etag-v4"']),
            'https://cdn.example/country-ipv6.mmdb' => Http::response('', 304, ['ETag' => '"etag-v6"']),
        ]);

        config([
            'ip-info.location_db.sources' => [
                'dbip' => [
                    'country' => [
                        'ipv4' => 'https://cdn.example/country-ipv4.mmdb',
                        'ipv6' => 'https://cdn.example/country-ipv6.mmdb',
                    ],
                ],
            ],
        ]);

        $report = $this->app->make(LocationDbDownloader::class)->download('country');

        $this->assertSame([], $report->downloaded);
        $this->assertCount(2, $report->notModified);
        $this->assertSame('existing-ipv4', file_get_contents($ipv4));
        Http::assertSentCount(2);
    }

    public function test_it_sends_conditional_headers_after_etag_saved(): void
    {
        $catalog = $this->app->make(LocationDbCatalog::class);
        $ipv4 = $catalog->filePath('country', 'ipv4');
        $ipv6 = $catalog->filePath('country', 'ipv6');
        $urlV4 = 'https://cdn.example/country-ipv4.mmdb';
        $urlV6 = 'https://cdn.example/country-ipv6.mmdb';

        config([
            'ip-info.location_db.sources' => [
                'dbip' => [
                    'country' => [
                        'ipv4' => $urlV4,
                        'ipv6' => $urlV6,
                    ],
                ],
            ],
        ]);

        Http::fake([
            $urlV4 => Http::sequence()
                ->push('first-ipv4', 200, ['ETag' => '"etag-v4"'])
                ->push('', 304, ['ETag' => '"etag-v4"']),
            $urlV6 => Http::sequence()
                ->push('first-ipv6', 200, ['ETag' => '"etag-v6"'])
                ->push('', 304, ['ETag' => '"etag-v6"']),
        ]);

        $downloader = $this->app->make(LocationDbDownloader::class);
        $first = $downloader->download('country');
        $second = $downloader->download('country');

        $this->assertCount(2, $first->downloaded);
        $this->assertSame([], $second->downloaded);
        $this->assertCount(2, $second->notModified);

        Http::assertSent(function ($request) use ($urlV4): bool {
            return $request->url() === $urlV4
                && $request->hasHeader('If-None-Match', '"etag-v4"');
        });
    }

    public function test_force_ignores_conditional_headers(): void
    {
        $catalog = $this->app->make(LocationDbCatalog::class);
        $ipv4 = $catalog->filePath('country', 'ipv4');
        $ipv6 = $catalog->filePath('country', 'ipv6');
        $urlV4 = 'https://cdn.example/country-ipv4.mmdb';
        $urlV6 = 'https://cdn.example/country-ipv6.mmdb';

        mkdir(dirname($ipv4), 0777, true);
        file_put_contents($ipv4, 'old-ipv4');
        file_put_contents($ipv6, 'old-ipv6');
        file_put_contents($catalog->metadataPath(), json_encode([
            'files' => [[
                'edition' => 'country',
                'ip_version' => 'ipv4',
                'path' => $ipv4,
                'url' => $urlV4,
                'etag' => '"etag-v4"',
            ]],
        ]));

        config([
            'ip-info.location_db.sources' => [
                'dbip' => [
                    'country' => [
                        'ipv4' => $urlV4,
                        'ipv6' => $urlV6,
                    ],
                ],
            ],
        ]);

        Http::fake([
            '*' => Http::response('fresh-bytes', 200, ['ETag' => '"etag-new"']),
        ]);

        $report = $this->app->make(LocationDbDownloader::class)->download('country', force: true);

        $this->assertCount(2, $report->downloaded);
        Http::assertSent(function ($request): bool {
            return ! $request->hasHeader('If-None-Match');
        });
    }

    public function test_missing_metadata_downloads_files(): void
    {
        $catalog = $this->app->make(LocationDbCatalog::class);
        $urlV4 = 'https://cdn.example/country-ipv4.mmdb';
        $urlV6 = 'https://cdn.example/country-ipv6.mmdb';

        config([
            'ip-info.location_db.sources' => [
                'dbip' => [
                    'country' => [
                        'ipv4' => $urlV4,
                        'ipv6' => $urlV6,
                    ],
                ],
            ],
        ]);

        Http::fake([
            '*' => Http::response('mmdb-bytes', 200, ['ETag' => '"etag"']),
        ]);

        $report = $this->app->make(LocationDbDownloader::class)->download('country');

        $this->assertCount(2, $report->downloaded);
        $this->assertFileExists($catalog->metadataPath());
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
