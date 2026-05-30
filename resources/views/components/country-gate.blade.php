<div {{ $attributes->class(['ip-info-country-gate']) }}>
    @if ($allowed)
        {{ $slot }}
    @elseif (isset($fallback))
        {{ $fallback }}
    @endif
</div>
