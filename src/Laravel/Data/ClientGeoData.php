<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Data;

use Illuminate\Http\Request;
use JsonSerializable;
use SuprunBohdan\IpInfo\Data\IpInfoResult;
use SuprunBohdan\IpInfo\Laravel\IpInfoManager;

final readonly class ClientGeoData implements JsonSerializable
{
    public function __construct(
        public string $ip,
        public ?string $countryCode,
        public ?string $countryName,
        public ?string $continent,
        public bool $isEu,
        public bool $isPublic,
        public bool $isPrivate,
        public ?string $city = null,
        public ?string $region = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    public static function fromRequest(?Request $request = null): self
    {
        $request ??= request();

        $cached = $request->attributes->get('ip_info');

        if ($cached instanceof IpInfoResult) {
            return self::fromResult($cached);
        }

        return self::fromResult(app(IpInfoManager::class)->forRequest($request)->result());
    }

    public static function fromResult(IpInfoResult $result): self
    {
        $coordinates = $result->coordinates();

        return new self(
            ip: $result->ip,
            countryCode: $result->countryCode(),
            countryName: $result->geo->countryName,
            continent: $result->geo->continent,
            isEu: $result->isEu(),
            isPublic: $result->isPublic,
            isPrivate: $result->isPrivate,
            city: $result->city(),
            region: $result->region(),
            latitude: $coordinates['lat'] ?? null,
            longitude: $coordinates['lon'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forFrontend(): array
    {
        $payload = [
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'continent' => $this->continent,
            'is_eu' => $this->isEu,
            'is_public' => $this->isPublic,
        ];

        if (config('ip-info.frontend.expose_city', false)) {
            $payload['city'] = $this->city;
            $payload['region'] = $this->region;
        }

        if (config('ip-info.frontend.expose_coordinates', false)) {
            $payload['lat'] = $this->latitude;
            $payload['lon'] = $this->longitude;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'continent' => $this->continent,
            'is_eu' => $this->isEu,
            'is_public' => $this->isPublic,
            'is_private' => $this->isPrivate,
            'city' => $this->city,
            'region' => $this->region,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
