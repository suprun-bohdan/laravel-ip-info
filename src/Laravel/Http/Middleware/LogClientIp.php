<?php

// @ip-info-stub-version 4.5.0

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Intel\ClientIpIntelBuilder;
use SuprunBohdan\IpInfo\Logging\ClientIpLogger;
use Symfony\Component\HttpFoundation\Response;

final class LogClientIp
{
    public function __construct(
        private ClientIpIntelBuilder $intelBuilder,
        private ClientIpLogger $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $intel = $request->attributes->get('client_ip_intel');

        if (! $intel instanceof \SuprunBohdan\IpInfo\Intel\ClientIpIntel) {
            $intel = $this->intelBuilder->fromRequest(
                $request,
                (bool) config('ip-info.whois.enabled', false)
                && (bool) config('ip-info.logging.include_whois', true),
            );
            $request->attributes->set('client_ip_intel', $intel);
        }

        $this->logger->logIntel($intel, $request);

        return $next($request);
    }
}
