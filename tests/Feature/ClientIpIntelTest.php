<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Contracts\WhoisClient;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Intel\ClientIpFilter;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\FilterClientIp;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\LogClientIp;
use SuprunBohdan\IpInfo\Tests\Support\FakeWhoisClient;
use SuprunBohdan\IpInfo\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ClientIpIntelTest extends TestCase
{
    public function test_client_ip_filter_blocks_tor_client(): void
    {
        config([
            'ip-info.filtering.enabled' => true,
            'ip-info.filtering.block_tor' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
        ]);

        IpInfo::fake(['198.96.155.10' => 'IS']);

        $intel = IpInfo::for('198.96.155.10')->intel();
        $filter = app(ClientIpFilter::class);

        $this->assertSame('tor', $filter->blockReason($intel));
    }

    public function test_client_ip_filter_blocks_whois_netname(): void
    {
        config([
            'ip-info.whois.enabled' => true,
            'ip-info.filtering.enabled' => true,
            'ip-info.filtering.blocked_netnames' => ['IS-VFIS-*'],
        ]);

        $fake = new FakeWhoisClient;
        $fake->register('whois.iana.org', '89.160.176.1', "refer:        whois.ripe.net\n");
        $fake->register('whois.ripe.net', '89.160.176.1', <<<'WHOIS'
inetnum: 89.160.176.0 - 89.160.183.255
netname: IS-VFIS-EAMAN
country: IS
WHOIS);
        $this->app->instance(WhoisClient::class, $fake);

        IpInfo::fake(['89.160.176.1' => 'IS']);

        $intel = IpInfo::for('89.160.176.1')->intel(withWhois: true);
        $filter = app(ClientIpFilter::class);

        $this->assertSame('whois_netname', $filter->blockReason($intel));
    }

    public function test_log_client_ip_middleware_builds_intel_context(): void
    {
        config([
            'ip-info.logging.enabled' => false,
            'ip-info.whois.enabled' => false,
        ]);

        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/dashboard', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $middleware = app(LogClientIp::class);
        $middleware->handle($request, fn () => response('ok'));

        $intel = $request->attributes->get('client_ip_intel');
        $this->assertInstanceOf(ClientIpIntel::class, $intel);
        $this->assertSame('UA', $intel->geo->countryCode());
    }

    public function test_filter_client_ip_middleware_aborts_on_blocked_tor(): void
    {
        config([
            'ip-info.filtering.enabled' => true,
            'ip-info.filtering.block_tor' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
        ]);

        IpInfo::fake(['198.96.155.10' => 'IS']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.96.155.10']);
        $middleware = app(FilterClientIp::class);

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_client_ip_intel_helper_returns_cached_attribute(): void
    {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $request->attributes->set('client_ip_intel', new ClientIpIntel(
            geo: new IpInfoResult('203.0.113.10', GeoLocation::fromCountryCode('UA'), true, false, 'fake'),
            privacy: ip_privacy('203.0.113.10'),
        ));

        $this->app->instance('request', $request);

        $this->assertSame('UA', client_ip_intel()->geo->countryCode());
    }
}
