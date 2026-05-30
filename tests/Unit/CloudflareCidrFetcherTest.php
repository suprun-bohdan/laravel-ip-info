<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Http\Client\Factory;
use SuprunBohdan\IpInfo\Laravel\Support\CloudflareCidrFetcher;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class CloudflareCidrFetcherTest extends TestCase
{
    public function test_parses_cloudflare_plain_text_lists(): void
    {
        $factory = new Factory;
        $factory->fake([
            'https://www.cloudflare.com/ips-v4' => Factory::response("173.245.48.0/20\n103.21.244.0/22\n"),
            'https://www.cloudflare.com/ips-v6' => Factory::response("2400:cb00::/32\n"),
        ]);

        $fetcher = new CloudflareCidrFetcher($factory);
        $cidrs = $fetcher->fetchAll();

        $this->assertContains('173.245.48.0/20', $cidrs);
        $this->assertContains('2400:cb00::/32', $cidrs);
        $this->assertStringContainsString('173.245.48.0/20', $fetcher->toEnvSnippet($cidrs));
    }
}
