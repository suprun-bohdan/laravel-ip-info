<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Intel;

final class ClientIpRiskScorer
{
    public function score(ClientIpIntel $intel): ClientIpRiskScore
    {
        if (! (bool) config('ip-info.risk.enabled', true)) {
            return ClientIpRiskScore::none();
        }

        $weights = config('ip-info.risk.weights', []);

        if (! is_array($weights)) {
            $weights = [];
        }

        $signals = [];
        $total = 0;
        $threats = $intel->threats();

        if ($threats !== null) {
            $total += $this->addSignal($signals, 'tor', $threats->isTor(), $weights);
            $total += $this->addSignal($signals, 'proxy', $threats->isProxy(), $weights);
            $total += $this->addSignal($signals, 'vpn', $threats->isVpn(), $weights);
            $total += $this->addSignal($signals, 'hosting', $threats->isHosting(), $weights);
        }

        if ($this->hasWhoisCountryMismatch($intel)) {
            $total += $this->addSignal($signals, 'whois_country_mismatch', true, $weights);
        }

        $score = min(100, $total);
        $thresholds = config('ip-info.risk.thresholds', ['medium' => 30, 'high' => 60]);

        if (! is_array($thresholds)) {
            $thresholds = ['medium' => 30, 'high' => 60];
        }

        $medium = (int) ($thresholds['medium'] ?? 30);
        $high = (int) ($thresholds['high'] ?? 60);

        $level = 'low';

        if ($score >= $high) {
            $level = 'high';
        } elseif ($score >= $medium) {
            $level = 'medium';
        }

        return new ClientIpRiskScore($score, $level, $signals);
    }

    /**
     * @param  list<array{reason: string, points: int}>  $signals
     * @param  array<string, mixed>  $weights
     */
    private function addSignal(array &$signals, string $reason, bool $active, array $weights): int
    {
        if (! $active || ! isset($weights[$reason])) {
            return 0;
        }

        $points = (int) $weights[$reason];

        if ($points <= 0) {
            return 0;
        }

        $signals[] = ['reason' => $reason, 'points' => $points];

        return $points;
    }

    private function hasWhoisCountryMismatch(ClientIpIntel $intel): bool
    {
        if ($intel->whois === null) {
            return false;
        }

        $geoCountry = $intel->geo->countryCode();
        $whoisCountry = $intel->whois->country;

        if ($geoCountry === null || $whoisCountry === null) {
            return false;
        }

        return strtoupper($geoCountry) !== strtoupper($whoisCountry);
    }
}
