<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Laravel\Http\Rules\ClientCountryIn;
use SuprunBohdan\IpInfo\Laravel\Http\Rules\ClientIpPublic;
use SuprunBohdan\IpInfo\Laravel\Http\Rules\CountryIn;
use SuprunBohdan\IpInfo\Laravel\Http\Rules\CountryNotIn;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ValidationRulesTest extends TestCase
{
    public function test_client_ip_public_rejects_private_ip(): void
    {
        $validator = Validator::make(
            ['ip' => '10.0.0.1'],
            ['ip' => [new ClientIpPublic]],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_client_ip_public_accepts_public_ip(): void
    {
        $validator = Validator::make(
            ['ip' => '8.8.8.8'],
            ['ip' => [new ClientIpPublic]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_country_in_accepts_matching_country(): void
    {
        IpInfo::fake(['8.8.8.8' => 'US']);

        $validator = Validator::make(
            ['ip' => '8.8.8.8'],
            ['ip' => [new CountryIn(['US', 'CA'])]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_country_in_rejects_non_matching_country(): void
    {
        IpInfo::fake(['8.8.8.8' => 'RU']);

        $validator = Validator::make(
            ['ip' => '8.8.8.8'],
            ['ip' => [new CountryIn(['US'])]],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_country_not_in_rejects_blocked_country(): void
    {
        IpInfo::fake(['8.8.8.8' => 'RU']);

        $validator = Validator::make(
            ['ip' => '8.8.8.8'],
            ['ip' => [new CountryNotIn(['RU'])]],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_client_country_in_accepts_allowed_request_country(): void
    {
        IpInfo::fake(['203.0.113.10' => 'UA']);

        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $this->app->instance('request', $request);

        $validator = Validator::make([], [
            '_geo' => [new ClientCountryIn(['UA'], $request)],
        ]);

        $this->assertFalse($validator->fails());
    }
}
