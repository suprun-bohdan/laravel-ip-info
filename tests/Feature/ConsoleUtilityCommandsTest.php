<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ConsoleUtilityCommandsTest extends TestCase
{
    public function test_publish_schedule_appends_stubs(): void
    {
        $target = base_path('routes/console.php');
        $dir = dirname($target);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($target, "<?php\n");

        $exitCode = Artisan::call('ip-info:publish-schedule');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('ip-info:update-database', (string) file_get_contents($target));

        @unlink($target);
    }

    public function test_publish_pest_creates_pest_file(): void
    {
        $target = base_path('tests/Pest.php');

        if (file_exists($target)) {
            unlink($target);
        }

        $exitCode = Artisan::call('ip-info:publish-pest');

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target);
        $this->assertStringContainsString('InteractsWithIpInfo', (string) file_get_contents($target));

        @unlink($target);
    }

    public function test_make_ip_info_test_creates_feature_test(): void
    {
        $target = base_path('tests/Feature/GeneratedIpInfoTest.php');

        if (file_exists($target)) {
            unlink($target);
        }

        $exitCode = Artisan::call('make:ip-info-test', ['name' => 'GeneratedIpInfo']);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target);
        $this->assertStringContainsString('IpInfo::fake', (string) file_get_contents($target));

        @unlink($target);
    }

    public function test_starter_kit_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('ip-info:starter', ['--preset' => 'local_only']);

        $this->assertSame(0, $exitCode);
    }
}
