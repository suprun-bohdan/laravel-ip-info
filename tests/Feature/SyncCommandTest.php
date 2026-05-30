<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use SuprunBohdan\IpInfo\Laravel\Sync\PackageStubs;
use SuprunBohdan\IpInfo\Laravel\Sync\PublishedFileComparator;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class SyncCommandTest extends TestCase
{
    public function test_sync_reports_missing_config(): void
    {
        $configPath = config_path('ip-info.php');

        if (file_exists($configPath)) {
            unlink($configPath);
        }

        Artisan::call('ip-info:sync', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('missing', $payload['config']['status']);
    }

    public function test_sync_reports_unsafe_trusted_headers(): void
    {
        config([
            'ip-info.trusted_proxies.headers' => ['CF-Connecting-IP'],
            'ip-info.trusted_proxies.proxy_cidrs' => [],
            'ip-info.trusted_proxies.require_trusted_proxy_for_headers' => true,
        ]);

        Artisan::call('ip-info:sync', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['security']['trusted_headers_without_proxy_cidrs']);
        $this->assertFalse($payload['healthy']);
    }

    public function test_sync_json_exit_code_when_unhealthy(): void
    {
        config([
            'ip-info.routes.enabled' => true,
            'ip-info.routes.middleware' => [],
        ]);

        $exitCode = Artisan::call('ip-info:sync', ['--json' => true]);

        $this->assertSame(1, $exitCode);
    }

    public function test_fix_publishes_missing_config(): void
    {
        $target = config_path('ip-info.php');

        if (file_exists($target)) {
            unlink($target);
        }

        Artisan::call('ip-info:sync', ['--publish-config' => true]);

        $this->assertFileExists($target);
    }

    public function test_fix_never_overwrites_modified_middleware(): void
    {
        $path = app_path('Http/Middleware/ResolveClientIp.php');

        if (file_exists($path)) {
            unlink($path);
        }

        Artisan::call('vendor:publish', ['--tag' => 'ip-info-middleware']);

        file_put_contents($path, (string) file_get_contents($path)."\n// custom change\n");

        Artisan::call('ip-info:sync', [
            '--publish-middleware' => true,
            '--force' => true,
        ]);

        $this->assertStringContainsString('custom change', (string) file_get_contents($path));
    }

    public function test_sync_detects_published_middleware(): void
    {
        $path = app_path('Http/Middleware/ResolveClientIp.php');

        if (file_exists($path)) {
            unlink($path);
        }

        Artisan::call('vendor:publish', ['--tag' => 'ip-info-middleware']);

        Artisan::call('ip-info:sync', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('ok', $payload['middleware']['published']);
    }

    public function test_sync_reports_middleware_registration_missing(): void
    {
        Artisan::call('vendor:publish', ['--tag' => 'ip-info-middleware']);

        $bootstrap = base_path('bootstrap/app.php');
        $dir = dirname($bootstrap);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($bootstrap, '<?php');

        Artisan::call('ip-info:sync', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('missing', $payload['middleware']['registered']);
        $this->assertFalse($payload['healthy']);

        @unlink($bootstrap);
    }

    public function test_preset_recommendations_report_only(): void
    {
        config(['ip-info.install_preset' => 'cloudflare']);

        $envPath = base_path('.env');
        $original = file_exists($envPath) ? (string) file_get_contents($envPath) : null;

        Artisan::call('ip-info:sync', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('cloudflare', $payload['preset']['name']);
        $this->assertNotEmpty($payload['preset']['env_recommendations']);

        if ($original === null) {
            $this->assertFileDoesNotExist($envPath);
        } else {
            $this->assertSame($original, file_get_contents($envPath));
        }
    }

    public function test_check_routes_only_mode(): void
    {
        config([
            'ip-info.routes.enabled' => true,
            'ip-info.routes.middleware' => ['throttle:60,1'],
        ]);

        $exitCode = Artisan::call('ip-info:sync', [
            '--check-routes' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('ok', $payload['routes']['status']);
        $this->assertTrue($payload['healthy']);
        $this->assertSame(0, $exitCode);
    }

    public function test_outdated_stub_detection(): void
    {
        $target = config_path('ip-info-sync-test.php');
        file_put_contents($target, "<?php\n// @ip-info-stub-version 1.0.0\n");

        $comparator = new PublishedFileComparator;
        $status = $comparator->compare($target, PackageStubs::configStubPath());

        $this->assertSame('outdated', $status->value);

        @unlink($target);
    }
}
