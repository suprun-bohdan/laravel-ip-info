<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Intel;

final class FilterBlockResponseResolver
{
    /**
     * @return array{status: int, message: string}
     */
    public function resolve(string $reason): array
    {
        $responses = config('ip-info.filtering.responses', []);

        if (is_array($responses)) {
            if (isset($responses[$reason]) && is_array($responses[$reason])) {
                return $this->normalize($responses[$reason]);
            }

            if (isset($responses['default']) && is_array($responses['default'])) {
                return $this->normalize($responses['default']);
            }
        }

        return [
            'status' => (int) config('ip-info.filtering.block_response_status', 403),
            'message' => (string) config(
                'ip-info.filtering.block_response_message',
                'Access from your network is not allowed.',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{status: int, message: string}
     */
    private function normalize(array $config): array
    {
        return [
            'status' => (int) ($config['status'] ?? config('ip-info.filtering.block_response_status', 403)),
            'message' => (string) ($config['message'] ?? config(
                'ip-info.filtering.block_response_message',
                'Access from your network is not allowed.',
            )),
        ];
    }
}
