<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

use SuprunBohdan\IpInfo\Data\IpAddress;
use SuprunBohdan\IpInfo\Data\IpInfoResult;

interface IpLookupContract
{
    public function lookup(IpAddress $address): IpInfoResult;

    public function isPublic(IpAddress $address): bool;

    public function isPrivate(IpAddress $address): bool;
}
