<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

final class PublishedFileComparator
{
    public function compare(string $publishedPath, string $vendorStubPath): SyncStatus
    {
        if (! file_exists($publishedPath)) {
            return SyncStatus::Missing;
        }

        if (! file_exists($vendorStubPath)) {
            return SyncStatus::Unknown;
        }

        $publishedContents = (string) file_get_contents($publishedPath);
        $vendorContents = (string) file_get_contents($vendorStubPath);

        if ($this->normalize($publishedContents) === $this->normalize($vendorContents)) {
            return SyncStatus::Ok;
        }

        $publishedVersion = $this->extractStubVersion($publishedContents);
        $vendorVersion = $this->extractStubVersion($vendorContents);

        if ($publishedVersion !== null && $vendorVersion !== null
            && version_compare($publishedVersion, $vendorVersion, '<')) {
            return SyncStatus::Outdated;
        }

        return SyncStatus::Modified;
    }

    public function canOverwrite(SyncStatus $status, bool $force): bool
    {
        return match ($status) {
            SyncStatus::Missing => true,
            SyncStatus::Outdated => $force,
            SyncStatus::Ok => false,
            SyncStatus::Modified => false,
            default => false,
        };
    }

    private function extractStubVersion(string $contents): ?string
    {
        if (preg_match(PackageStubs::STUB_VERSION_PATTERN, $contents, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function normalize(string $contents): string
    {
        return str_replace("\r\n", "\n", trim($contents));
    }
}
