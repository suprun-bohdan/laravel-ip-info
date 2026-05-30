<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Sync;

final class PresetRecommendationBuilder
{
    /**
     * @return array{
     *     name: ?string,
     *     env_recommendations: list<string>,
     *     config_recommendations: array<string, mixed>
     * }
     */
    public function build(?string $presetName = null): array
    {
        $name = $presetName;

        if ($name === null || $name === '') {
            $name = config('ip-info.install_preset');

            if (! is_string($name) || $name === '') {
                $name = null;
            }
        }

        if ($name === null) {
            return [
                'name' => null,
                'env_recommendations' => [],
                'config_recommendations' => [],
            ];
        }

        $presets = config('ip-info.presets', []);

        if (! isset($presets[$name]) || ! is_array($presets[$name])) {
            return [
                'name' => $name,
                'env_recommendations' => [],
                'config_recommendations' => [],
            ];
        }

        /** @var array<string, mixed> $preset */
        $preset = $presets[$name];

        return [
            'name' => $name,
            'env_recommendations' => $this->envRecommendations($name, $preset),
            'config_recommendations' => $preset,
        ];
    }

    /**
     * @param  array<string, mixed>  $preset
     * @return list<string>
     */
    private function envRecommendations(string $name, array $preset): array
    {
        $recommendations = [
            'IP_INFO_PRESET='.$name,
        ];

        if (isset($preset['trusted_proxies']) && is_array($preset['trusted_proxies'])) {
            $trusted = $preset['trusted_proxies'];

            if (array_key_exists('respect_laravel', $trusted)) {
                $recommendations[] = 'IP_INFO_RESPECT_LARAVEL_PROXIES='
                    .($trusted['respect_laravel'] ? 'true' : 'false');
            }

            if (array_key_exists('require_trusted_proxy_for_headers', $trusted)) {
                $recommendations[] = 'IP_INFO_REQUIRE_TRUSTED_PROXY='
                    .($trusted['require_trusted_proxy_for_headers'] ? 'true' : 'false');
            }

            if (in_array($name, ['cloudflare', 'cloudflare_strict'], true)) {
                $recommendations[] = 'IP_INFO_TRUSTED_PROXY_CIDRS=<cloudflare-egress-cidrs>';
            }
        }

        if (isset($preset['providers']) && is_array($preset['providers'])) {
            $recommendations[] = '# Merge providers.chain into config/ip-info.php from preset';
        }

        if (isset($preset['database']['enabled'])) {
            $recommendations[] = 'IP_INFO_DATABASE_ENABLED='
                .($preset['database']['enabled'] ? 'true' : 'false');
        }

        if (isset($preset['http']['enabled'])) {
            $recommendations[] = 'IP_INFO_HTTP_ENABLED='
                .($preset['http']['enabled'] ? 'true' : 'false');
        }

        return $recommendations;
    }
}
