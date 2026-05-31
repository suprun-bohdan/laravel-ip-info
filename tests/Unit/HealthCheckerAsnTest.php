<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\LocationDb\LocationDbCatalog;
use SuprunBohdan\IpInfo\Laravel\Sync\HealthChecker;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class HealthCheckerAsnTest extends TestCase
{
    public function test_asn_db_is_stale_when_enrichment_enabled_but_files_missing(): void
    {
        config([
            'ip-info.location_db.enrich_asn' => true,
            'ip-info.location_db.storage_dir' => sys_get_temp_dir().'/missing-asn-'.uniqid('', true),
        ]);

        $checker = $this->app->make(HealthChecker::class);

        $this->assertFalse($checker->asnDbIsInstalled());
        $this->assertTrue($checker->asnDbIsStale());
        $this->assertFalse($checker->isDataHealthy());
    }

    public function test_asn_db_health_is_ignored_when_enrichment_disabled(): void
    {
        config(['ip-info.location_db.enrich_asn' => false]);

        $checker = $this->app->make(HealthChecker::class);

        $this->assertFalse($checker->asnDbIsStale());
        $this->assertFalse($checker->asnDbIsInstalled());
    }

    public function test_asn_db_is_installed_when_both_ip_versions_exist(): void
    {
        $storageDir = sys_get_temp_dir().'/ip-info-asn-health-'.uniqid('', true);
        mkdir($storageDir, 0755, true);
        config([
            'ip-info.location_db.enrich_asn' => true,
            'ip-info.location_db.storage_dir' => $storageDir,
        ]);

        $catalog = $this->app->make(LocationDbCatalog::class);
        file_put_contents($catalog->filePath('asn', 'ipv4'), 'v4');
        file_put_contents($catalog->filePath('asn', 'ipv6'), 'v6');

        $checker = $this->app->make(HealthChecker::class);

        $this->assertTrue($checker->asnDbIsInstalled());
        $this->assertFalse($checker->asnDbIsStale());

        @unlink($catalog->filePath('asn', 'ipv4'));
        @unlink($catalog->filePath('asn', 'ipv6'));
        @rmdir($storageDir);
    }
}
