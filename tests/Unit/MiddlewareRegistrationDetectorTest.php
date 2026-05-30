<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Laravel\Sync\MiddlewareRegistrationDetector;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class MiddlewareRegistrationDetectorTest extends TestCase
{
    public function test_it_detects_registered_middleware_in_bootstrap_app(): void
    {
        $bootstrap = base_path('bootstrap/app.php');
        $dir = dirname($bootstrap);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($bootstrap, <<<'PHP'
<?php
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\ResolveClientIp;
ResolveClientIp::class;
PHP);

        $detector = new MiddlewareRegistrationDetector;

        $this->assertSame('registered', $detector->detect());

        @unlink($bootstrap);
    }

    public function test_it_reports_missing_when_bootstrap_exists_without_reference(): void
    {
        $bootstrap = base_path('bootstrap/app.php');
        $dir = dirname($bootstrap);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($bootstrap, '<?php // no middleware');

        $detector = new MiddlewareRegistrationDetector;

        $this->assertSame('missing', $detector->detect());

        @unlink($bootstrap);
    }
}
