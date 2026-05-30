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

            config(['ip-info.'.$section => array_replace_recursive($current, $values)]);
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
}
