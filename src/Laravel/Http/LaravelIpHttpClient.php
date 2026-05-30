<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Http;

use Illuminate\Http\Client\Factory;
use SuprunBohdan\IpInfo\Contracts\IpHttpClient;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;
use SuprunBohdan\IpInfo\Http\IpHttpResponse;

final class LaravelIpHttpClient implements IpHttpClient
{
    public function __construct(private Factory $http) {}

    public function get(string $url, int $timeoutSeconds): IpHttpResponse
    {
        try {
            $response = $this->http
                ->timeout($timeoutSeconds)
                ->withOptions(['allow_redirects' => false])
                ->get($url);

            $json = $response->json();

            return new IpHttpResponse(
                $response->status(),
                is_array($json) ? $json : null,
            );
        } catch (\Throwable $exception) {
            throw new ProviderException('HTTP request failed: '.$exception->getMessage(), 0, $exception);
        }
    }
}
