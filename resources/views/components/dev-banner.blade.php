@props([
    'showIp' => true,
    'showThreats' => true,
    'onlyPrivate' => false,
])

@php
    use SuprunBohdan\IpInfo\Laravel\Data\ClientGeoData;

    $geo = ClientGeoData::fromRequest();
    $threats = ip_info()->threats();
    $hasThreats = $threats->isTor() || $threats->isProxy() || $threats->isVpn() || $threats->isHosting();
    $visible = ! ($onlyPrivate && ! $geo->isPrivate && ! $hasThreats);
    $anonymizedIp = ip_info()->result()->anonymized()->ip;
@endphp

@if ($visible)
    <div {{ $attributes->class(['ip-info-banner']) }}>
        <div class="ip-info-banner__title">Client IP intelligence</div>
        <div class="ip-info-banner__meta">
            <span class="ip-info-badge">
                {{ $geo->countryCode ?? '??' }}
            </span>
            @if ($geo->countryName)
                <span class="ip-info-banner__text">{{ $geo->countryName }}</span>
            @endif
            @if ($geo->continent)
                <span class="ip-info-banner__text">{{ $geo->continent }}</span>
            @endif
            @if ($geo->isEu)
                <span class="ip-info-chip ip-info-chip--eu">EU</span>
            @endif
        </div>
        @if ($showIp)
            <div class="ip-info-banner__ip">
                <span class="ip-info-banner__label">IP</span>
                <code>{{ $anonymizedIp }}</code>
            </div>
        @endif
        @if ($showThreats)
            <div class="ip-info-banner__chips">
                @if ($geo->isPrivate)
                    <span class="ip-info-chip ip-info-chip--private">private</span>
                @endif
                @if ($threats->isTor())
                    <span class="ip-info-chip ip-info-chip--tor">tor</span>
                @endif
                @if ($threats->isProxy())
                    <span class="ip-info-chip ip-info-chip--proxy">proxy</span>
                @endif
                @if ($threats->isVpn())
                    <span class="ip-info-chip ip-info-chip--vpn">vpn</span>
                @endif
                @if ($threats->isHosting())
                    <span class="ip-info-chip ip-info-chip--hosting">hosting</span>
                @endif
            </div>
        @endif
    </div>
@endif
