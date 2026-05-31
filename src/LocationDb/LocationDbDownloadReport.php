<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\LocationDb;

final readonly class LocationDbDownloadReport
{
    /**
     * @param  list<string>  $downloaded
     * @param  list<string>  $notModified
     */
    public function __construct(
        public array $downloaded = [],
        public array $notModified = [],
    ) {}

    public function hasChanges(): bool
    {
        return $this->downloaded !== [] || $this->notModified !== [];
    }
}
