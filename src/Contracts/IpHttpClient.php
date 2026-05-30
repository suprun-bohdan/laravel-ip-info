<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Contracts;

use SuprunBohdan\IpInfo\Http\IpHttpResponse;

interface IpHttpClient
{
    public function get(string $url, int $timeoutSeconds): IpHttpResponse;
}
