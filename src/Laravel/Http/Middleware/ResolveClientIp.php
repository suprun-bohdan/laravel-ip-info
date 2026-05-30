<?php

// @ip-info-stub-version 4.6.0

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;
use Symfony\Component\HttpFoundation\Response;

final class ResolveClientIp
{
    public function __construct(private IpInfoManager $ipInfo) {}

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->ipInfo->forRequest($request)->result();

        $request->attributes->set('ip_info', $result);
        $request->attributes->set('client_ip', $result->ip);

        return $next($request);
    }
}
