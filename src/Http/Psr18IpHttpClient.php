<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuprunBohdan\IpInfo\Contracts\IpHttpClient;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;

/**
 * PSR-18 HTTP adapter.
 *
 * The `$timeoutSeconds` argument is accepted for interface compatibility but is
 * **not enforced** here because PSR-18 does not define timeout configuration.
 * Configure timeouts on the injected {@see ClientInterface} implementation, or
 * use {@see LaravelIpHttpClient} which enforces timeout via Laravel HTTP.
 */
final class Psr18IpHttpClient implements IpHttpClient
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
    ) {}

    public function get(string $url, int $timeoutSeconds): IpHttpResponse
    {
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->client->sendRequest($request);

            $body = (string) $response->getBody();
            $json = json_decode($body, true);

            return new IpHttpResponse(
                $response->getStatusCode(),
                is_array($json) ? $json : null,
            );
        } catch (\Throwable $exception) {
            throw new ProviderException('HTTP request failed: '.$exception->getMessage(), 0, $exception);
        }
    }
}
