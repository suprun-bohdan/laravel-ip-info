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
        $this->assertArrayNotHasKey('city', $geo->forFrontend());
    }

    public function test_for_frontend_exposes_city_when_enabled(): void
    {
        config(['ip-info.frontend.expose_city' => true]);

        $provider = new class implements \SuprunBohdan\IpInfo\Contracts\IpProvider
        {
            public function lookup(\SuprunBohdan\IpInfo\Data\IpAddress $ip): \SuprunBohdan\IpInfo\Data\ProviderResult
            {
                return \SuprunBohdan\IpInfo\Data\ProviderResult::hit(
                    'UA',
                    'test',
                    new \SuprunBohdan\IpInfo\Data\GeoLocation('UA', city: 'Kyiv', region: '30'),
                );
            }
        };

        $this->app->make(\SuprunBohdan\IpInfo\Contracts\IpProviderResolver::class)
            ->replace(new \SuprunBohdan\IpInfo\Providers\ChainProvider([$provider]));

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        (new ShareClientGeo)->handle($request, fn () => response('ok'));

        $payload = ClientGeoData::fromRequest($request)->forFrontend();

        $this->assertSame('Kyiv', $payload['city']);
        $this->assertSame('30', $payload['region']);
    }

    public function test_for_frontend_excludes_coordinates_by_default(): void
    {
        $provider = new class implements \SuprunBohdan\IpInfo\Contracts\IpProvider
        {
            public function lookup(\SuprunBohdan\IpInfo\Data\IpAddress $ip): \SuprunBohdan\IpInfo\Data\ProviderResult
            {
                return \SuprunBohdan\IpInfo\Data\ProviderResult::hit(
                    'UA',
                    'test',
                    new \SuprunBohdan\IpInfo\Data\GeoLocation('UA', latitude: 50.45, longitude: 30.52),
                );
            }
        };

        $this->app->make(\SuprunBohdan\IpInfo\Contracts\IpProviderResolver::class)
            ->replace(new \SuprunBohdan\IpInfo\Providers\ChainProvider([$provider]));

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        (new ShareClientGeo)->handle($request, fn () => response('ok'));

        $payload = ClientGeoData::fromRequest($request)->forFrontend();

        $this->assertArrayNotHasKey('lat', $payload);
        $this->assertArrayNotHasKey('lon', $payload);
    }

    public function test_for_frontend_exposes_coordinates_when_enabled(): void
    {
        config(['ip-info.frontend.expose_coordinates' => true]);

        $provider = new class implements \SuprunBohdan\IpInfo\Contracts\IpProvider
        {
            public function lookup(\SuprunBohdan\IpInfo\Data\IpAddress $ip): \SuprunBohdan\IpInfo\Data\ProviderResult
            {
                return \SuprunBohdan\IpInfo\Data\ProviderResult::hit(
                    'UA',
                    'test',
                    new \SuprunBohdan\IpInfo\Data\GeoLocation('UA', latitude: 50.45, longitude: 30.52),
                );
            }
        };

        $this->app->make(\SuprunBohdan\IpInfo\Contracts\IpProviderResolver::class)
            ->replace(new \SuprunBohdan\IpInfo\Providers\ChainProvider([$provider]));

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        (new ShareClientGeo)->handle($request, fn () => response('ok'));

        $payload = ClientGeoData::fromRequest($request)->forFrontend();

        $this->assertSame(50.45, $payload['lat']);
        $this->assertSame(30.52, $payload['lon']);
    }
}
