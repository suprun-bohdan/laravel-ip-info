<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Contracts\IpProvider;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Providers\ChainProvider;

final class ChainProviderExceptionTest extends TestCase
{
    public function test_it_falls_through_when_provider_throws(): void
    {
        $failing = new class implements IpProvider
        {
            public function lookup(IpAddress $ip): ProviderResult
            {
                throw new ProviderException('failed');
            }
        };

        $success = new class implements IpProvider
        {
            public function lookup(IpAddress $ip): ProviderResult
            {
                return new ProviderResult('UA', 'custom', true);
            }
        };

        $chain = new ChainProvider([$failing, $success]);
        $result = $chain->lookup(new IpAddress('8.8.8.8'));

        $this->assertTrue($result->resolved);
        $this->assertSame('UA', $result->countryCode);
    }
}
