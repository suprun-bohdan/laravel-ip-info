<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SuprunBohdan\IpInfo\Laravel\Data\ClientGeoData;

/** @mixin ClientGeoData */
final class ClientGeoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ClientGeoData $geo */
        $geo = $this->resource;

        return $geo->forFrontend();
    }
}
