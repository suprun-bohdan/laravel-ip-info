<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Laravel\Sync\HealthChecker;
use SuprunBohdan\IpInfo\Laravel\Sync\IpInfoSyncInspector;
use SuprunBohdan\IpInfo\Laravel\Sync\MiddlewareRegistrationDetector;
use SuprunBohdan\IpInfo\Laravel\Sync\PresetRecommendationBuilder;
use SuprunBohdan\IpInfo\Laravel\Sync\PublishedFileComparator;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInfoSyncInspectorTest extends TestCase
{
    private string $consolePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consolePath = base_path('routes/console.php');
        $dir = dirname($this->consolePath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->consolePath)) {
            @unlink($this->consolePath);
        }

        parent::tearDown();
    }

    public function test_schedule_stub_missing_when_location_db_enabled_without_stub(): void
    {
        config([
            'ip-info.database.enabled' => false,
            'ip-info.location_db.enabled' => true,
        ]);

        file_put_contents($this->consolePath, "<?php\nSchedule::monthly()->command('ip-info:update-database');\n");

        $report = $this->inspector()->inspect();

        $this->assertContains(
            'Append schedule stubs: php artisan ip-info:install --with-schedule',
            $report->actions,
        );
    }

    public function test_schedule_stub_ok_when_required_stubs_present(): void
    {
        config([
            'ip-info.database.enabled' => true,
            'ip-info.location_db.enabled' => true,
        ]);

        file_put_contents(
            $this->consolePath,
            "<?php\nSchedule::monthly()->command('ip-info:update-database');\nSchedule::monthly()->command('ip-info:update-location-db');\n",
        );

        $report = $this->inspector()->inspect();

        $this->assertNotContains(
            'Append schedule stubs: php artisan ip-info:install --with-schedule',
            $report->actions,
        );
    }

    private function inspector(): IpInfoSyncInspector
    {
        return new IpInfoSyncInspector(
            $this->app->make(PublishedFileComparator::class),
            $this->app->make(HealthChecker::class),
            $this->app->make(MiddlewareRegistrationDetector::class),
            $this->app->make(PresetRecommendationBuilder::class),
        );
    }
}
