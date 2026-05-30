<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Exceptions\InvalidIpAddressException;
use SuprunBohdan\IpInfo\Support\IpNormalizer;
use SuprunBohdan\IpInfo\Support\IpValidator;

final class IpValidatorTest extends TestCase
{
    private IpValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new IpValidator;
    }

    public function test_it_detects_valid_public_ipv4(): void
    {
        $this->assertTrue($this->validator->isValid('8.8.8.8'));
        $this->assertTrue($this->validator->isPublic('8.8.8.8'));
    }

    public function test_it_detects_invalid_ipv4(): void
    {
        $this->assertFalse($this->validator->isValid('999.999.999.999'));
    }

    public function test_it_detects_localhost(): void
    {
        $this->assertTrue($this->validator->isLocalhost('127.0.0.1'));
        $this->assertTrue($this->validator->isPrivate('127.0.0.1'));
    }

    public function test_it_detects_private_ipv4_ranges(): void
    {
        $this->assertTrue($this->validator->isPrivate('10.0.0.1'));
        $this->assertTrue($this->validator->isPrivate('172.16.0.1'));
        $this->assertTrue($this->validator->isPrivate('192.168.1.1'));
    }

    public function test_it_detects_ipv4_link_local(): void
    {
        $this->assertTrue($this->validator->isLinkLocal('169.254.1.1'));
    }

    public function test_it_detects_valid_public_ipv6(): void
    {
        $this->assertTrue($this->validator->isValid('2001:4860:4860::8888'));
        $this->assertTrue($this->validator->isPublic('2001:4860:4860::8888'));
    }

    public function test_it_detects_ipv6_localhost(): void
    {
        $this->assertTrue($this->validator->isLocalhost('::1'));
        $this->assertTrue($this->validator->isPrivate('::1'));
    }

    public function test_it_detects_ipv6_unique_local(): void
    {
        $this->assertTrue($this->validator->isPrivate('fd12:3456:789a:1::1'));
    }

    public function test_it_detects_ipv4_test_net_as_reserved(): void
    {
        $this->assertTrue($this->validator->isReserved('192.0.2.1'));
        $this->assertTrue($this->validator->isReserved('198.51.100.10'));
        $this->assertTrue($this->validator->isReserved('203.0.113.50'));
        $this->assertTrue($this->validator->shouldSkipExternalLookup('192.0.2.1'));
    }

    public function test_it_detects_ipv6_unspecified_as_reserved(): void
    {
        $this->assertTrue($this->validator->isReserved('::'));
        $this->assertTrue($this->validator->shouldSkipExternalLookup('::'));
    }

    public function test_it_detects_ipv6_multicast_as_reserved(): void
    {
        $this->assertTrue($this->validator->isReserved('ff02::1'));
        $this->assertTrue($this->validator->shouldSkipExternalLookup('ff02::1'));
    }

    public function test_ipv4_mapped_private_address_is_private(): void
    {
        $normalizer = new IpNormalizer;
        $normalized = $normalizer->normalize('::ffff:10.0.0.1');

        $this->assertSame('10.0.0.1', $normalized);
        $this->assertTrue($this->validator->isPrivate($normalized));
        $this->assertFalse($this->validator->isPublic($normalized));
    }
}

final class IpNormalizerTest extends TestCase
{
    private IpNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new IpNormalizer;
    }

    public function test_it_trims_input(): void
    {
        $this->assertSame('8.8.8.8', $this->normalizer->normalize('  8.8.8.8  '));
    }

    public function test_it_rejects_multiple_addresses(): void
    {
        $this->expectException(InvalidIpAddressException::class);
        $this->normalizer->normalize('1.1.1.1, 2.2.2.2');
    }

    public function test_it_normalizes_ipv4_mapped_ipv6(): void
    {
        $this->assertSame('192.0.2.1', $this->normalizer->normalize('::ffff:192.0.2.1'));
    }

    public function test_it_canonicalizes_expanded_ipv6(): void
    {
        $this->assertSame('2001:4860:4860::8888', $this->normalizer->normalize('2001:4860:4860:0:0:0:0:8888'));
    }

    public function test_it_anonymizes_ipv6_to_network_prefix(): void
    {
        $this->assertSame('2001:4860:4860::', $this->normalizer->anonymize('2001:4860:4860::8888'));
    }
}
