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
        return new self(
            ip: $result->ip,
            countryCode: $result->countryCode(),
            countryName: $result->geo->countryName,
            continent: $result->geo->continent,
            isEu: $result->isEu(),
            isPublic: $result->isPublic,
            isPrivate: $result->isPrivate,
        );
    }

    /**
     * @return array{
     *     country_code: ?string,
     *     country_name: ?string,
     *     continent: ?string,
     *     is_eu: bool,
     *     is_public: bool
     * }
     */
    public function forFrontend(): array
    {
        return [
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'continent' => $this->continent,
            'is_eu' => $this->isEu,
            'is_public' => $this->isPublic,
        ];
    }

    /**
     * @return array{
     *     ip: string,
     *     country_code: ?string,
     *     country_name: ?string,
     *     continent: ?string,
     *     is_eu: bool,
     *     is_public: bool,
     *     is_private: bool
     * }
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
        ];
    }

    /**
     * @return array{
     *     ip: string,
     *     country_code: ?string,
     *     country_name: ?string,
     *     continent: ?string,
     *     is_eu: bool,
     *     is_public: bool,
     *     is_private: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
