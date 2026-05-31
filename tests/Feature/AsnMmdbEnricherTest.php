<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use Mockery;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\LocationDb\MmdbReaderPool;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class AsnMmdbEnricherTest extends TestCase
{
    private const PUBLIC_IPV4 = '8.8.8.8';

    public function test_it_enriches_geo_with_asn_when_enabled(): void
    {
        config([
            'ip-info.providers.chain' => ['location_db', 'null'],
            'ip-info.location_db.enabled' => true,
            'ip-info.location_db.enrich_asn' => true,
            'ip-info.lookup.request_memo' => false,
        ]);

        $storageDir = sys_get_temp_dir().'/ip-info-asn-'.uniqid('', true);
        mkdir($storageDir, 0755, true);
        config(['ip-info.location_db.storage_dir' => $storageDir]);

        file_put_contents($storageDir.'/country-ipv4.mmdb', 'v4');
        file_put_contents($storageDir.'/country-ipv6.mmdb', 'v6');
        file_put_contents($storageDir.'/asn-ipv4.mmdb', 'v4');
        file_put_contents($storageDir.'/asn-ipv6.mmdb', 'v6');

        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4)
            ->andReturn(['country_code' => 'US']);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4, 'asn')
            ->andReturn([
                'autonomous_system_number' => 15169,
                'autonomous_system_organization' => 'GOOGLE',
            ]);

        $this->instance(MmdbReaderPool::class, $pool);
        $this->app->forgetInstance('ip-info');

        $result = IpInfo::for(self::PUBLIC_IPV4)->result();

        $this->assertSame('US', $result->countryCode());
        $this->assertSame(15169, $result->asn());
        $this->assertSame('GOOGLE', $result->asnOrganization());

        @unlink($storageDir.'/country-ipv4.mmdb');
        @unlink($storageDir.'/country-ipv6.mmdb');
        @unlink($storageDir.'/asn-ipv4.mmdb');
        @unlink($storageDir.'/asn-ipv6.mmdb');
        @rmdir($storageDir);
    }

    public function test_asn_country_edition_resolves_country(): void
    {
        config([
            'ip-info.providers.chain' => ['location_db', 'null'],
            'ip-info.location_db.enabled' => true,
            'ip-info.location_db.edition' => 'asn_country',
        ]);

        $pool = Mockery::mock(MmdbReaderPool::class);
        $pool->shouldReceive('lookup')
            ->once()
            ->with(self::PUBLIC_IPV4)
            ->andReturn(['country_code' => 'DE']);

        $this->instance(MmdbReaderPool::class, $pool);
        $this->app->forgetInstance('ip-info');

        $this->assertSame('DE', IpInfo::for(self::PUBLIC_IPV4)->countryCode());
    }
}
