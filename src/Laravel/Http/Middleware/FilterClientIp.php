<?php

// @ip-info-stub-version 4.5.0

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Intel\ClientIpFilter;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;
use SuprunBohdan\IpInfo\Intel\ClientIpIntelBuilder;
use SuprunBohdan\IpInfo\Intel\FilterBlockResponseResolver;
use SuprunBohdan\IpInfo\Laravel\Events\ClientIpBlocked;
use Symfony\Component\HttpFoundation\Response;

final class FilterClientIp
{
    public function __construct(
        private ClientIpIntelBuilder $intelBuilder,
        private ClientIpFilter $filter,
        private FilterBlockResponseResolver $responseResolver,
        private Dispatcher $events,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $intel = $request->attributes->get('client_ip_intel');

        if (! $intel instanceof ClientIpIntel) {
            $intel = $this->intelBuilder->fromRequest(
                $request,
                $this->intelBuilder->filterNeedsWhois(),
            );
            $request->attributes->set('client_ip_intel', $intel);
        }

        $reason = $this->filter->blockReason($intel);

        if ($reason !== null) {
            $response = $this->responseResolver->resolve($reason);

            $this->events->dispatch(new ClientIpBlocked(
                $request,
                $intel,
                $reason,
                $response['status'],
                $response['message'],
            ));

            $headers = [];

            if ((bool) config('ip-info.filtering.expose_block_reason_header', false)) {
                $headers['X-Ip-Info-Block-Reason'] = $reason;
            }

            abort($response['status'], $response['message'], $headers);
        }

        return $next($request);
    }
}
