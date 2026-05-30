<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

use SuprunBohdan\IpInfo\Data\IpAddress;

interface IpResolver
{
    public function resolve(mixed $source): IpAddress;
}
