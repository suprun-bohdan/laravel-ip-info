<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use SuprunBohdan\IpInfo\Intel\ClientIpIntel;
use SuprunBohdan\IpInfo\Intel\ClientIpIntelBuilder;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupCompleted;

final class ClientIpLogger
{
    public function __construct(private ClientIpIntelBuilder $intelBuilder) {}

    public function logIntel(ClientIpIntel $intel, ?Request $request = null): void
    {
        if (! (bool) config('ip-info.logging.enabled', false)) {
            return;
        }

        if (! $intel->shouldLog()) {
            return;
        }

        $context = $intel->forLogging(
            includeWhois: (bool) config('ip-info.logging.include_whois', true),
            includeThreats: (bool) config('ip-info.logging.include_threats', true),
        );

        if ($request !== null) {
            $context['request'] = [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
            ];
        }

        $this->logger()->log(
            (string) config('ip-info.logging.level', 'info'),
            (string) config('ip-info.logging.message', 'Client IP intelligence'),
            $context,
        );
    }

    public function logRequest(Request $request): void
    {
        $cached = $request->attributes->get('client_ip_intel');

        if ($cached instanceof ClientIpIntel) {
            $this->logIntel($cached, $request);

            return;
        }

        $this->logIntel($this->intelBuilder->fromRequest($request), $request);
    }

    public function handleLookupCompleted(IpLookupCompleted $event): void
    {
        if (! (bool) config('ip-info.logging.auto_log_on_lookup', false)) {
            return;
        }

        $this->logIntel($this->intelBuilder->fromResult($event->result));
    }

    private function logger(): LoggerInterface
    {
        $channel = config('ip-info.logging.channel');

        if (is_string($channel) && $channel !== '') {
            return Log::channel($channel);
        }

        return Log::getLogger();
    }
}
