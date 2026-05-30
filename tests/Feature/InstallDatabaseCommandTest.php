<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class InstallDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_installs_database_from_faked_csv_download(): void
    {
        Storage::fake('local');

        Http::fake([
            '*' => Http::response("1.0.0.0,1.0.0.255,US\n", 200),
        ]);

        $exitCode = Artisan::call('ip-info:install-database');

        $this->assertSame(0, $exitCode);
        Storage::disk('local')->assertExists('asn-country-ipv4.csv');
        $this->assertDatabaseHas('ip_country', ['country' => 'US']);
    }
}

final class UpdateDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_database_with_force_download(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('asn-country-ipv4.csv', "old\n");

        Http::fake([
            '*' => Http::response("2.0.0.0,2.0.0.255,DE\n", 200),
        ]);

        $exitCode = Artisan::call('ip-info:update-database', ['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('ip_country', ['country' => 'DE']);
    }
}
