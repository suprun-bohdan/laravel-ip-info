<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use SuprunBohdan\IpInfo\Http\HttpCircuitBreaker;

final class AboutCommand extends Command
{
    protected $signature = 'ip-info:about';

    protected $description = 'Display Laravel IP Info capabilities and enabled providers.';

    public function handle(HttpCircuitBreaker $circuitBreaker): int
    {
        $driver = (string) config('ip-info.http.driver', 'ip-api');

        $rows = [
            ['Package', 'suprun-bohdan/laravel-ip-info'],
            ['Cache', config('ip-info.cache.enabled') ? 'enabled' : 'disabled'],
            ['Database provider', config('ip-info.database.enabled') ? 'enabled' : 'disabled'],
            ['MaxMind provider', config('ip-info.maxmind.enabled') ? 'enabled' : 'disabled'],
            ['HTTP provider', config('ip-info.http.enabled') ? 'enabled ('.$driver.')' : 'disabled'],
            ['HTTP circuit open', $circuitBreaker->isOpen('http:'.$driver) ? 'yes' : 'no'],
            ['CleanTalk provider', config('ip-info.cleantalk.enabled') ? 'enabled' : 'disabled'],
            ['Request memo', config('ip-info.lookup.request_memo') ? 'enabled' : 'disabled'],
            ['Privacy skip private', config('ip-info.privacy.skip_private_ips') ? 'enabled' : 'disabled'],
            ['Tenant cache prefix', config('ip-info.cache.tenant_prefix') ?: 'none'],
        ];

        $this->table(['Setting', 'Value'], $rows);
        $this->newLine();
        $this->line('Presets: cloudflare, cloudflare_strict, nginx_proxy, local_only');

        return self::SUCCESS;
    }
}
