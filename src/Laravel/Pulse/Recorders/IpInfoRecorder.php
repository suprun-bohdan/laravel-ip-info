<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Pulse\Recorders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Pulse\Contracts\Ingest;
use SuprunBohdan\IpInfo\Laravel\Events\IpLookupCompleted;

final class IpInfoRecorder
{
    /** @var array<string, int> */
    private array $countries = [];

    private int $lookups = 0;

    private int $cacheHits = 0;

    public function __construct(
        private Repository $config,
        private Dispatcher $events,
    ) {
        $this->events->listen(IpLookupCompleted::class, [$this, 'record']);
    }

    public function record(IpLookupCompleted $event): void
    {
        if (! $this->config->get('ip-info.pulse.enabled', true)) {
            return;
        }

        if (! interface_exists(Ingest::class)) {
            return;
        }

        $this->lookups++;

        if ($event->result->provider === 'cache' || $event->result->provider === 'cache:negative') {
            $this->cacheHits++;
        }

        $country = $event->result->countryCode();

        if (is_string($country) && $country !== '') {
            $this->countries[$country] = ($this->countries[$country] ?? 0) + 1;
        }

        $pulse = app('Laravel\Pulse\Pulse');

        if (! is_object($pulse) || ! method_exists($pulse, 'set')) {
            return;
        }

        $pulse->set('ip_info_lookups', $this->lookups);
        $pulse->set('ip_info_cache_hits', $this->cacheHits);

        arsort($this->countries);
        $pulse->set('ip_info_top_countries', array_slice($this->countries, 0, 5, true));
    }
}
