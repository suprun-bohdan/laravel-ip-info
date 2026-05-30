<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use SuprunBohdan\IpInfo\Data\ProviderResult;
use SuprunBohdan\IpInfo\Data\ProviderStatus;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ProviderResultTest extends TestCase
{
    public function test_legacy_resolved_property_matches_status(): void
    {
        $this->assertTrue(ProviderResult::hit('US', 'test')->resolved);
        $this->assertTrue(ProviderResult::miss('test')->resolved);
        $this->assertFalse(ProviderResult::skipped('test')->resolved);
        $this->assertFalse(ProviderResult::failed('test')->resolved);
    }

    public function test_should_stop_chain_for_hit_and_miss_only(): void
    {
        $this->assertTrue(ProviderResult::hit('US', 'test')->shouldStopChain());
        $this->assertTrue(ProviderResult::miss('test')->shouldStopChain());
        $this->assertFalse(ProviderResult::skipped('test')->shouldStopChain());
        $this->assertFalse(ProviderResult::failed('test')->shouldStopChain());
    }

    public function test_from_legacy_maps_old_semantics(): void
    {
        $hit = ProviderResult::fromLegacy('US', 'db', true);
        $miss = ProviderResult::fromLegacy(null, 'db', true);
        $skip = ProviderResult::fromLegacy(null, 'db', false);

        $this->assertSame(ProviderStatus::Hit, $hit->status);
        $this->assertSame(ProviderStatus::Miss, $miss->status);
        $this->assertSame(ProviderStatus::Skipped, $skip->status);
    }
}
