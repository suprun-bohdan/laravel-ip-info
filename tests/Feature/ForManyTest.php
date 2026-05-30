<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Feature;

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;
use SuprunBohdan\IpInfo\Tests\TestCase;

final class ForManyTest extends TestCase
{
    public function test_for_many_returns_results_keyed_by_ip(): void
    {
        IpInfo::fake([
            '8.8.8.8' => 'US',
            '1.1.1.1' => 'AU',
        ]);

        $results = IpInfo::forMany(['8.8.8.8', '1.1.1.1']);

        $this->assertSame('US', $results['8.8.8.8']->countryCode());
        $this->assertSame('AU', $results['1.1.1.1']->countryCode());
    }
}
