<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;

final class IpInfoController extends Controller
{
    public function __construct(private IpInfoManager $ipInfo) {}

    public function show(Request $request): JsonResponse
    {
        $query = $this->ipInfo->forRequest($request);
        $result = $query->result();

        return response()->json([
            'ip' => $result->ip,
            'country' => $result->countryCode(),
            'is_public' => $result->isPublic,
            'is_private' => $result->isPrivate,
            'provider' => $result->provider,
        ]);
    }
}
