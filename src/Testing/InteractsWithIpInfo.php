<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Testing;

use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

trait InteractsWithIpInfo
{
    /**
     * @param  array<string, string|null>  $map
     */
    protected function fakeIpInfo(array $map = []): FakeIpProvider
    {
        return IpInfo::fake($map);
    }
}
