<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Database\Concerns;

use Illuminate\Http\Request;
use SuprunBohdan\IpInfo\Laravel\Facades\IpInfo;

trait HasGeoFromIp
{
    public function fillGeoFromRequest(Request $request, string $column = 'country_code'): static
    {
        $country = IpInfo::forRequest($request)->countryCode();

        if ($country !== null) {
            $this->{$column} = strtoupper($country);
        }

        return $this;
    }

    public function fillGeoFromIp(string $ip, string $column = 'country_code'): static
    {
        $country = IpInfo::for($ip)->countryCode();

        if ($country !== null) {
            $this->{$column} = strtoupper($country);
        }

        return $this;
    }
}
