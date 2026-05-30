<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Pulse\Livewire;

use Illuminate\Contracts\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
final class IpInfoCard extends Card
{
    public function render(): View
    {
        return view('ip-info::pulse.ip-info-card', [
            'lookups' => $this->aggregate('ip_info_lookups', 'count'),
            'cacheHits' => $this->aggregate('ip_info_cache_hits', 'count'),
        ]);
    }
}
