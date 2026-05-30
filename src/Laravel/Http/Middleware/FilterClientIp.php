<?php

// @ip-info-stub-version 4.3.0

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Intel\ClientIpFilter;
use SuprunBohdan\IpInfo\Intel\ClientIpIntelBuilder;
use Symfony\Component\HttpFoundation\Response;

final class FilterClientIp
{
    public function __construct(
        private ClientIpIntelBuilder $intelBuilder,
        private ClientIpFilter $filter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $intel = $request->attributes->get('client_ip_intel');

        if (! $intel instanceof \SuprunBohdan\IpInfo\Intel\ClientIpIntel) {
            $intel = $this->intelBuilder->fromRequest(
                $request,
                $this->intelBuilder->filterNeedsWhois(),
            );
            $request->attributes->set('client_ip_intel', $intel);
        }

        $reason = $this->filter->blockReason($intel);

        if ($reason !== null) {
            abort(
                (int) config('ip-info.filtering.block_response_status', 403),
                (string) config(
                    'ip-info.filtering.block_response_message',
                    'Access from your network is not allowed.',
                ),
            );
        }

        return $next($request);
    }
}
