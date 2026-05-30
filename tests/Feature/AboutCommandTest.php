<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class AboutCommandTest extends TestCase
{
    public function test_about_command_runs(): void
    {
        Artisan::call('ip-info:about');

        $this->assertStringContainsString('suprun-bohdan/laravel-ip-info', Artisan::output());
    }
}
