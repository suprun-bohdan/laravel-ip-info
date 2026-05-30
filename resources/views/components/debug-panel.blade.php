@props([
    'collapsed' => true,
])

@php
    $intel = client_ip_intel();
    $payload = $intel->forLogging(includeWhois: false, includeThreats: true);
@endphp

<details {{ $attributes->class(['ip-info-debug-panel']) }} @if (! $collapsed) open @endif>
    <summary class="ip-info-debug-panel__summary">IP Info debug</summary>
    <pre class="ip-info-debug-panel__body">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</details>
