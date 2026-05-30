<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Data\ClientGeoData;
use Symfony\Component\HttpFoundation\Response;

final class ShareClientGeo
{
    public function handle(Request $request, Closure $next): Response
    {
        $geo = ClientGeoData::fromRequest($request);

        $request->attributes->set('client_geo', $geo);

        if (class_exists(\Inertia\Inertia::class)) {
            \Inertia\Inertia::share('geo', $geo->forFrontend());
        }

        return $next($request);
    }
}
