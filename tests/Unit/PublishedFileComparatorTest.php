<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SuprunBohdan\IpInfo\Laravel\Sync\PackageStubs;
use SuprunBohdan\IpInfo\Laravel\Sync\PublishedFileComparator;
use SuprunBohdan\IpInfo\Laravel\Sync\SyncStatus;

final class PublishedFileComparatorTest extends TestCase
{
    private PublishedFileComparator $comparator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->comparator = new PublishedFileComparator;
    }

    public function test_it_detects_missing_published_file(): void
    {
        $status = $this->comparator->compare(
            sys_get_temp_dir().'/missing-ip-info-config.php',
            PackageStubs::configStubPath(),
        );

        $this->assertSame(SyncStatus::Missing, $status);
    }

    public function test_it_detects_identical_stub_as_ok(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'ip-info-config-');
        copy(PackageStubs::configStubPath(), (string) $temp);

        $status = $this->comparator->compare((string) $temp, PackageStubs::configStubPath());

        $this->assertSame(SyncStatus::Ok, $status);

        @unlink((string) $temp);
    }

    public function test_it_detects_modified_file(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'ip-info-config-');
        file_put_contents((string) $temp, "<?php\n// @ip-info-stub-version 4.5.0\n// custom\n");

        $status = $this->comparator->compare((string) $temp, PackageStubs::configStubPath());

        $this->assertSame(SyncStatus::Modified, $status);

        @unlink((string) $temp);
    }

    public function test_can_overwrite_rules(): void
    {
        $this->assertTrue($this->comparator->canOverwrite(SyncStatus::Missing, false));
        $this->assertTrue($this->comparator->canOverwrite(SyncStatus::Outdated, true));
        $this->assertFalse($this->comparator->canOverwrite(SyncStatus::Modified, true));
    }
}
