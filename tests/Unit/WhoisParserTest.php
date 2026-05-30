<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Tests\TestCase;
use SuprunBohdan\IpInfo\Whois\WhoisParser;

final class WhoisParserTest extends TestCase
{
    public function test_it_parses_ripe_inetnum_and_route_fields(): void
    {
        $parser = new WhoisParser;
        $record = $parser->parse('89.160.176.1', $this->ripeSample(), 'whois.ripe.net');

        $this->assertSame('89.160.176.0 - 89.160.183.255', $record->inetnum);
        $this->assertSame('IS-VFIS-EAMAN', $record->netname);
        $this->assertSame('IS', $record->country);
        $this->assertSame('abuse@vodafone.is', $record->abuseEmail);
        $this->assertSame('89.160.160.0/19', $record->route);
        $this->assertSame('AS12969', $record->originAsn);
        $this->assertSame('whois.ripe.net', $record->registry);
    }

    public function test_it_extracts_referral_server(): void
    {
        $parser = new WhoisParser;

        $this->assertSame(
            'whois.ripe.net',
            $parser->referralServer("refer:        whois.ripe.net\n"),
        );
    }

    private function ripeSample(): string
    {
        return <<<'WHOIS'
inetnum: 89.160.176.0 - 89.160.183.255
netname: IS-VFIS-EAMAN
descr: EAMAN Customers
descr: Vodafone Iceland
country: IS
status: ASSIGNED PA
source: RIPE

role: Vodafone Iceland Network Operations
abuse-mailbox: abuse@vodafone.is
source: RIPE # Filtered

route: 89.160.160.0/19
origin: AS12969
source: RIPE
WHOIS;
    }
}
