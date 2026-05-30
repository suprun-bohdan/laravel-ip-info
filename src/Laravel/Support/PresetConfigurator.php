<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Support;

final class PresetConfigurator
{
    public function apply(?string $presetName = null): bool
    {
        $name = $presetName ?? $this->resolveActivePresetName();

        if ($name === null || $name === '') {
            return false;
        }

        $presets = config('ip-info.presets', []);

        if (! is_array($presets) || ! isset($presets[$name]) || ! is_array($presets[$name])) {
            return false;
        }

        $this->mergePreset($presets[$name]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $preset
     */
    public function mergePreset(array $preset): void
    {
        foreach ($preset as $section => $values) {
            if (! is_array($values)) {
                continue;
            }

            $current = config('ip-info.'.$section, []);

            if (! is_array($current)) {
                $current = [];
            }

            config(['ip-info.'.$section => $this->mergeSection($current, $values)]);
        }
    }

    public function resolveActivePresetName(): ?string
    {
        $active = config('ip-info.active_preset');

        if (is_string($active) && $active !== '') {
            return $active;
        }

        $legacy = config('ip-info.install_preset');

        if (is_string($legacy) && $legacy !== '') {
            return $legacy;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function mergeSection(array $current, array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value) && array_is_list($value)) {
                $current[$key] = $value;

                continue;
            }

            if (is_array($value)) {
                $existing = $current[$key] ?? [];
                $current[$key] = is_array($existing)
                    ? array_replace_recursive($existing, $value)
                    : $value;

                continue;
            }

            $current[$key] = $value;
        }

        return $current;
    }
}
