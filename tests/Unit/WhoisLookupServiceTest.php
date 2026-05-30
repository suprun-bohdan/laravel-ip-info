<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use SuprunBohdan\IpInfo\Contracts\WhoisClient;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpValidator;
use SuprunBohdan\IpInfo\Tests\Support\FakeWhoisClient;
use SuprunBohdan\IpInfo\Tests\TestCase;
use SuprunBohdan\IpInfo\Whois\WhoisLookupService;
use SuprunBohdan\IpInfo\Whois\WhoisParser;

final class WhoisLookupServiceTest extends TestCase
{
    public function test_it_follows_iana_referral_and_parses_ripe_record(): void
    {
        config([
            'ip-info.whois.enabled' => true,
            'ip-info.whois.cache_ttl' => 0,
        ]);

        $fake = new FakeWhoisClient;
        $fake->register('whois.iana.org', '89.160.176.1', "refer:        whois.ripe.net\n");
        $fake->register('whois.ripe.net', '89.160.176.1', <<<'WHOIS'
inetnum: 89.160.176.0 - 89.160.183.255
netname: IS-VFIS-EAMAN
country: IS
route: 89.160.160.0/19
origin: AS12969
WHOIS);

        $this->app->instance(WhoisClient::class, $fake);

        $service = new WhoisLookupService(
            $fake,
            new WhoisParser,
            new IpNormalizer,
            new IpValidator,
            new Repository(new ArrayStore),
        );

        $record = $service->lookup('89.160.176.1', force: true);

        $this->assertNotNull($record);
        $this->assertSame('IS-VFIS-EAMAN', $record->netname);
        $this->assertSame('IS', $record->country);
        $this->assertSame('AS12969', $record->originAsn);
    }
}
