<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Events;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;

final class ClientIpBlocked
{
    public function __construct(
        public Request $request,
        public ClientIpIntel $intel,
        public string $reason,
        public int $status,
        public string $message,
    ) {}
}
