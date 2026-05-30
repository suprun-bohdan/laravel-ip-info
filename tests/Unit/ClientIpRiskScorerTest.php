<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Data\IpThreatSignals;
use SuprunBohdan\IpInfo\Data\WhoisRecord;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;
use SuprunBohdan\IpInfo\Intel\ClientIpRiskScorer;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ClientIpRiskScorerTest extends TestCase
{
    public function test_it_scores_tor_as_high_risk(): void
    {
        config([
            'ip-info.risk.enabled' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
        ]);

        $intel = IpInfo::for('198.96.155.10')->intel();
        $score = app(ClientIpRiskScorer::class)->score($intel);

        $this->assertSame(40, $score->score);
        $this->assertSame('medium', $score->level);
        $this->assertSame('tor', $score->signals[0]['reason']);
    }

    public function test_it_returns_none_when_disabled(): void
    {
        config(['ip-info.risk.enabled' => false]);

        $intel = new ClientIpIntel(
            geo: new IpInfoResult('8.8.8.8', GeoLocation::fromCountryCode('US'), true, false, 'fake'),
            privacy: ip_privacy('8.8.8.8'),
            whois: null,
        );

        $score = app(ClientIpRiskScorer::class)->score($intel);

        $this->assertSame(0, $score->score);
        $this->assertSame('low', $score->level);
        $this->assertSame([], $score->signals);
    }

    public function test_it_adds_whois_country_mismatch_points(): void
    {
        config(['ip-info.risk.enabled' => true]);

        $intel = new ClientIpIntel(
            geo: new IpInfoResult('89.160.176.1', GeoLocation::fromCountryCode('UA'), true, false, 'fake'),
            privacy: ip_privacy('89.160.176.1'),
            whois: new WhoisRecord(
                ip: '89.160.176.1',
                inetnum: null,
                netname: null,
                descriptions: [],
                country: 'IS',
                organization: null,
                abuseEmail: null,
                status: null,
                route: null,
                originAsn: null,
                source: null,
                registry: null,
            ),
        );

        $score = app(ClientIpRiskScorer::class)->score($intel);

        $this->assertSame(20, $score->score);
        $this->assertSame('low', $score->level);
        $this->assertSame('whois_country_mismatch', $score->signals[0]['reason']);
    }

    public function test_client_ip_intel_exposes_risk(): void
    {
        config([
            'ip-info.risk.enabled' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
        ]);

        IpInfo::fake(['198.96.155.10' => 'IS']);

        $risk = IpInfo::for('198.96.155.10')->intel()->risk();

        $this->assertTrue($risk->isMedium());
    }
}
