<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Data\ClientGeoData;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Middleware\ShareClientGeo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ClientGeoDataTest extends TestCase
{
    public function test_from_request_builds_frontend_payload(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        (new ShareClientGeo)->handle($request, fn () => response('ok'));

        $geo = ClientGeoData::fromRequest($request);

        $this->assertSame('UA', $geo->countryCode);
        $this->assertSame('UA', $geo->forFrontend()['country_code']);
        $this->assertArrayHasKey('client_geo', $request->attributes->all());
    }
}
