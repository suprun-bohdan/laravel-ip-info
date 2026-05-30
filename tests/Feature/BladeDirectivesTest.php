<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Support\Facades\View;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class BladeDirectivesTest extends TestCase
{
    public function test_country_directive_renders_matching_content(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);
        $this->app->instance('request', request()->create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']));

        $compiled = View::make('ip-info::tests.country', [])->render();

        $this->assertSame('visible', trim($compiled));
    }
}
