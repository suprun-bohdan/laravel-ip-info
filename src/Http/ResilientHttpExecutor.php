<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Http;

use SuprunBohdan\IpInfo\Contracts\IpHttpClient;
use SuprunBohdan\IpInfo\Exceptions\ProviderException;

final class ResilientHttpExecutor
{
    public function __construct(
        private IpHttpClient $client,
        private HttpCircuitBreaker $circuitBreaker,
    ) {}

    /**
     * @return array{response: ?IpHttpResponse, circuit_open: bool, soft_fail: bool}
     */
    public function get(string $circuitKey, string $url, int $timeoutSeconds, string $configPrefix = 'ip-info.http'): array
    {
        if ($this->circuitBreaker->isOpen($circuitKey)) {
            return ['response' => null, 'circuit_open' => true, 'soft_fail' => true];
        }

        $retries = max(0, (int) config($configPrefix.'.retries', 1));
        $softFailStatuses = config($configPrefix.'.soft_fail_statuses', [429, 500, 502, 503, 504]);
        $softFailStatuses = is_array($softFailStatuses) ? $softFailStatuses : [429, 500, 502, 503, 504];

        $attempts = $retries + 1;
        $lastResponse = null;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $lastResponse = $this->client->get($url, $timeoutSeconds);

                if ($lastResponse->ok()) {
                    $this->circuitBreaker->recordSuccess($circuitKey);

                    return ['response' => $lastResponse, 'circuit_open' => false, 'soft_fail' => false];
                }

                if (in_array($lastResponse->statusCode, $softFailStatuses, true)) {
                    $this->circuitBreaker->recordFailure($circuitKey);

                    return ['response' => null, 'circuit_open' => false, 'soft_fail' => true];
                }

                throw new ProviderException('HTTP request failed with HTTP '.$lastResponse->statusCode);
            } catch (ProviderException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                if ($attempt >= $attempts - 1) {
                    $this->circuitBreaker->recordFailure($circuitKey);

                    throw new ProviderException('HTTP request failed: '.$exception->getMessage(), 0, $exception);
                }

                usleep(random_int(10_000, 50_000));
            }
        }

        if ($lastResponse !== null && ! $lastResponse->ok()) {
            $this->circuitBreaker->recordFailure($circuitKey);

            return ['response' => null, 'circuit_open' => false, 'soft_fail' => true];
        }

        return ['response' => $lastResponse, 'circuit_open' => false, 'soft_fail' => false];
    }
}
