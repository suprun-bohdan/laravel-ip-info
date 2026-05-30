<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class CountryGate extends Component
{
    public bool $allowed;

    /**
     * @param  array<int, string>|string  $countries
     */
    public function __construct(array|string $countries = [])
    {
        if (is_string($countries)) {
            $countries = array_values(array_filter(array_map('trim', explode(',', $countries))));
        }

        $this->allowed = $countries !== [] && ip_info()->isCountry(...$countries);
    }

    public function render(): View
    {
        return view('ip-info::components.country-gate');
    }
}
