<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel;

use SuprunBohdan\IpInfo\Contracts\IpLookupContract;
use SuprunBohdan\IpInfo\Data\GeoLocation;
use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;

final class IpInfoQuery
{
    private ?IpInfoResult $resolved = null;

    public function __construct(
        private IpLookupContract $manager,
        private IpAddress $address,
    ) {}

    public function ip(): string
    {
        return $this->address->value;
    }

    public function countryCode(): ?string
    {
        return $this->result()->countryCode();
    }

    public function geo(): GeoLocation
    {
        return $this->result()->geo;
    }

    public function isPublic(): bool
    {
        return $this->manager->isPublic($this->address);
    }

    public function isPrivate(): bool
    {
        return $this->manager->isPrivate($this->address);
    }

    public function result(): IpInfoResult
    {
        if ($this->resolved === null) {
            $this->resolved = $this->manager->lookup($this->address);
        }

        return $this->resolved;
    }
}
