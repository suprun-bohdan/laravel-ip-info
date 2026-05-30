<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;
use SuprunBohdan\IpInfo\Logging\ClientIpLogger;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ClientIpLoggerTest extends TestCase
{
    public function test_it_respects_privacy_policy_for_private_ips(): void
    {
        config([
            'ip-info.logging.enabled' => true,
            'ip-info.privacy.skip_private_ips' => true,
        ]);

        $logger = app(ClientIpLogger::class);
        $intel = new ClientIpIntel(
            geo: new IpInfoResult('10.0.0.1', GeoLocation::fromCountryCode(null), false, true, 'local'),
            privacy: ip_privacy('10.0.0.1'),
        );

        $this->assertFalse($intel->shouldLog());
        $logger->logIntel($intel);
        $this->assertTrue(true);
    }
}
