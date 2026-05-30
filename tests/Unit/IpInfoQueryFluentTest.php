<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Exceptions\IpInfoException;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class IpInfoQueryFluentTest extends TestCase
{
    public function test_is_country_and_country_or(): void
    {
        IpInfo::fake(['8.8.8.8' => 'US']);

        $query = IpInfo::for('8.8.8.8');

        $this->assertTrue($query->isCountry('US', 'CA'));
        $this->assertSame('US', $query->countryOr('XX'));
    }

    public function test_country_or_fail_throws_when_missing(): void
    {
        IpInfo::fake(['10.0.0.1' => null]);

        $this->expectException(IpInfoException::class);

        IpInfo::for('10.0.0.1')->countryOrFail();
    }

    public function test_result_fluent_helpers(): void
    {
        $result = new IpInfoResult(
            '8.8.8.8',
            GeoLocation::fromCountryCode('DE'),
            true,
            false,
            'fake',
        );

        $this->assertTrue($result->isCountry('DE'));
        $this->assertTrue($result->inCountries(['DE', 'FR']));
        $this->assertSame('DE', $result->countryOr('XX'));
    }
}
