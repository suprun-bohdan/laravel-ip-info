<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Rules\ClientIpNotTor;
use SuprunBohdan\IpInfo\Laravel\Http\Rules\ValidNormalizedIp;
use SuprunBohdan\IpInfo\Providers\HttpIpProvider;
use SuprunBohdan\IpInfo\Support\IpThreatInspector;
use SuprunBohdan\IpInfo\Support\RequestProxyInspector;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInspectionTest extends TestCase
{
    public function test_normalize_ip_canonicalizes_ipv6(): void
    {
        $this->assertSame('2001:4860:4860::8888', normalize_ip('2001:4860:4860:0:0:0:0:8888'));
    }

    public function test_normalize_ip_strips_zone_identifier(): void
    {
        $this->assertSame('::1', normalize_ip('::1%lo0'));
    }

    public function test_ip_privacy_profile_classifies_private_ip(): void
    {
        $profile = ip_privacy('10.0.0.1');

        $this->assertTrue($profile->isPrivate);
        $this->assertFalse($profile->isPublic);
        $this->assertTrue($profile->shouldSkipExternalLookup);
        $this->assertSame('10.0.0.0', $profile->anonymizedIp());
    }

    public function test_ip_threats_detects_tor_from_configured_cidr(): void
    {
        config([
            'ip-info.threat_intel.enabled' => true,
            'ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24'],
        ]);

        $signals = app(IpThreatInspector::class)->inspect('198.96.155.10');

        $this->assertTrue($signals->isTor());
        $this->assertFalse($signals->isProxy());
    }

    public function test_ip_info_query_exposes_threat_and_privacy_methods(): void
    {
        config(['ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24']]);

        $query = IpInfo::for('198.96.155.10');

        $this->assertTrue($query->isTor());
        $this->assertTrue($query->privacy()->isPublic);
        $this->assertSame('198.96.155.10', $query->normalizedIp());
    }

    public function test_http_provider_enriches_proxy_and_hosting_signals(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'country' => 'United States',
                'countryCode' => 'US',
                'proxy' => true,
                'hosting' => false,
            ], 200),
        ]);

        config([
            'ip-info.http.enabled' => true,
            'ip-info.http.driver' => 'ip-api',
            'ip-info.http.allow_insecure' => true,
            'ip-info.http.enrich_threat_signals' => true,
        ]);

        $provider = $this->app->make(HttpIpProvider::class);
        $result = $provider->lookup(new IpAddress('8.8.8.8'));

        $this->assertTrue($result->isHit());
        $this->assertTrue($result->threats?->isProxy());
        $this->assertFalse($result->threats?->isHosting());
    }

    public function test_request_proxy_inspector_detects_trusted_proxy(): void
    {
        config([
            'ip-info.trusted_proxies.proxy_cidrs' => ['173.245.48.0/20'],
            'ip-info.trusted_proxies.require_trusted_proxy_for_headers' => true,
        ]);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '173.245.48.10']);
        $inspector = app(RequestProxyInspector::class);

        $this->assertTrue($inspector->isBehindTrustedProxy($request));
        $this->assertTrue(request_behind_trusted_proxy($request));
    }

    public function test_request_macros_expose_inspection_helpers(): void
    {
        config(['ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24']]);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.96.155.10']);
        $this->app->instance('request', $request);

        $this->assertTrue($request->isTorClient());
        $this->assertSame('198.96.155.10', $request->normalizedClientIp());
        $this->assertTrue($request->clientIpPrivacy()->isPublic);
    }

    public function test_client_ip_not_tor_validation_rule_fails_for_tor_ip(): void
    {
        config(['ip-info.threat_intel.tor_exit_cidrs' => ['198.96.155.0/24']]);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.96.155.10']);
        $this->app->instance('request', $request);

        $validator = Validator::make(['ip' => 'ignored'], ['ip' => [new ClientIpNotTor]]);

        $this->assertTrue($validator->fails());
    }

    public function test_valid_normalized_ip_rule_accepts_canonicalizable_ipv6(): void
    {
        $validator = Validator::make(
            ['ip' => '2001:4860:4860:0:0:0:0:8888'],
            ['ip' => [new ValidNormalizedIp]],
        );

        $this->assertFalse($validator->fails());
    }
}
