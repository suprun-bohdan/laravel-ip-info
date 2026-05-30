<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Client\Factory;
use SuprunBohdan\IpInfo\Laravel\Support\CloudflareCidrFetcher;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class RefreshCloudflareCidrsCommandTest extends TestCase
{
    public function test_command_outputs_env_snippet(): void
    {
        $factory = new Factory;
        $factory->fake([
            'https://www.cloudflare.com/ips-v4' => Factory::response("173.245.48.0/20\n"),
            'https://www.cloudflare.com/ips-v6' => Factory::response("2400:cb00::/32\n"),
        ]);

        $this->app->instance(CloudflareCidrFetcher::class, new CloudflareCidrFetcher($factory));

        $this->artisan('ip-info:refresh-cloudflare-cidrs', ['--write-env-snippet' => true])
            ->expectsOutputToContain('IP_INFO_TRUSTED_PROXY_CIDRS=')
            ->assertSuccessful();
    }
}
