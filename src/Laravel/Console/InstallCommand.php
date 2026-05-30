<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use SuprunBohdan\IpInfo\Laravel\Support\PresetConfigurator;

final class InstallCommand extends Command
{
    protected $signature = 'ip-info:install
                            {--preset= : Apply a named preset (cloudflare, nginx_proxy, local_only, offline, quick_start)}
                            {--quick : Enable HTTP geo via quick_start preset}
                            {--with-database : Download and seed the offline IPv4 database}
                            {--with-location-db=* : Download offline MMDB edition (country or city)}
                            {--with-schedule : Append update schedule stubs to routes/console.php}
                            {--register-middleware : Register ResolveClientIp middleware in bootstrap/app.php or Kernel.php}
                            {--with-blade : Publish Blade component views and CSS (tag ip-info-blade)}
                            {--force : Overwrite published config}';

    protected $description = 'Publish config, run migrations, and optionally apply a preset or offline database.';

    public function handle(
        PresetConfigurator $presetConfigurator,
        ScheduleStubPublisher $schedulePublisher,
        MiddlewareRegistrar $middlewareRegistrar,
    ): int {
        $this->info('Installing Laravel IP Info...');

        $publishOptions = ['--tag' => 'ip-info-config'];

        if ($this->option('force')) {
            $publishOptions['--force'] = true;
        }

        Artisan::call('vendor:publish', $publishOptions);
        $this->line(trim(Artisan::output()));

        if ($this->option('quick')) {
            $this->applyPreset('quick_start', $presetConfigurator);
            $this->printQuickStartEnvSnippet();
        } else {
            $preset = $this->option('preset');

            if (is_string($preset) && $preset !== '') {
                $this->applyPreset($preset, $presetConfigurator);
            } elseif (is_string(config('ip-info.install_preset')) && config('ip-info.install_preset') !== '') {
                $this->applyPreset((string) config('ip-info.install_preset'), $presetConfigurator);
            }
        }

        $this->call('migrate', ['--force' => true]);

        if ($this->option('with-database')) {
            $this->call('ip-info:install-database');
        }

        $locationDb = $this->option('with-location-db');
        $edition = $this->resolveLocationDbEdition($locationDb);

        if ($edition !== null) {
            if (! in_array($edition, ['country', 'city'], true)) {
                $this->error('Location DB edition must be country or city.');

                return self::FAILURE;
            }

            config([
                'ip-info.location_db.enabled' => true,
                'ip-info.location_db.edition' => $edition,
            ]);

            $exitCode = $this->call('ip-info:update-location-db', [
                '--edition' => $edition,
                '--force' => (bool) $this->option('force'),
            ]);

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }

            $this->printLocationDbEnvSnippet($edition);
        }

        if ($this->option('with-schedule')) {
            $schedulePublisher->appendToConsole($this);
        }

        if ($this->option('register-middleware')) {
            $middlewareRegistrar->register($this, (bool) $this->option('force'));
        }

        if ($this->option('with-blade')) {
            Artisan::call('vendor:publish', [
                '--tag' => 'ip-info-blade',
                '--force' => (bool) $this->option('force'),
            ]);
            $this->line(trim(Artisan::output()));
            $this->line('Add to your layout: <link rel="stylesheet" href="'.asset('vendor/ip-info/ip-info-blade.css').'">');
        }

        $this->info('Laravel IP Info installed.');
        $this->line('Run php artisan ip-info:sync --json to audit application integration.');

        return self::SUCCESS;
    }

    private function applyPreset(string $name, PresetConfigurator $presetConfigurator): void
    {
        $presets = config('ip-info.presets', []);

        if (! isset($presets[$name]) || ! is_array($presets[$name])) {
            $this->warn("Unknown preset [{$name}]. Skipping.");

            return;
        }

        $presetConfigurator->mergePreset($presets[$name]);
        $this->info("Applied preset [{$name}]. Update .env or config/ip-info.php to persist.");
    }

    private function printQuickStartEnvSnippet(): void
    {
        $this->newLine();
        $this->line('Suggested .env entries for quick start:');
        $this->line('IP_INFO_PRESET=quick_start');
        $this->line('IP_INFO_HTTP_ENABLED=true');
        $this->line('IP_INFO_HTTP_DRIVER=ipinfo');
        $this->warn('Public HTTP lookups are subject to provider rate limits. Use offline location DB or MaxMind in production.');
    }

    private function printLocationDbEnvSnippet(string $edition): void
    {
        $this->newLine();
        $this->line('Suggested .env entries for offline location DB:');
        $this->line('IP_INFO_PRESET=offline');
        $this->line('IP_INFO_LOCATION_DB_ENABLED=true');
        $this->line('IP_INFO_LOCATION_DB_EDITION='.$edition);
        $this->line('composer require maxmind-db/reader');
        $this->warn('DB-IP Lite data is CC BY 4.0 — attribute https://db-ip.com/ when displaying geo data.');
    }

    private function resolveLocationDbEdition(mixed $locationDb): ?string
    {
        if ($locationDb === false || $locationDb === null) {
            return null;
        }

        if (is_array($locationDb)) {
            if ($locationDb === []) {
                return null;
            }

            $value = $locationDb[0] ?? null;

            if ($value === null || $value === true || $value === '') {
                return 'country';
            }

            return (string) $value;
        }

        if ($locationDb === true || $locationDb === '') {
            return 'country';
        }

        return (string) $locationDb;
    }
}
