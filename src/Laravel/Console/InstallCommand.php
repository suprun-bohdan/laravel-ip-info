<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class InstallCommand extends Command
{
    protected $signature = 'ip-info:install
                            {--preset= : Apply a named preset (cloudflare, nginx_proxy, local_only)}
                            {--with-database : Download and seed the offline IPv4 database}
                            {--force : Overwrite published config}';

    protected $description = 'Publish config, run migrations, and optionally apply a preset or offline database.';

    public function handle(): int
    {
        $this->info('Installing Laravel IP Info...');

        $publishOptions = ['--tag' => 'ip-info-config'];

        if ($this->option('force')) {
            $publishOptions['--force'] = true;
        }

        Artisan::call('vendor:publish', $publishOptions);
        $this->line(trim(Artisan::output()));

        $preset = $this->option('preset');

        if (is_string($preset) && $preset !== '') {
            $this->applyPreset($preset);
        } elseif (is_string(config('ip-info.install_preset')) && config('ip-info.install_preset') !== '') {
            $this->applyPreset((string) config('ip-info.install_preset'));
        }

        $this->call('migrate', ['--force' => true]);

        if ($this->option('with-database')) {
            $this->call('ip-info:install-database');
        }

        $this->info('Laravel IP Info installed.');
        $this->line('Run php artisan ip-info:sync --json to audit application integration.');

        return self::SUCCESS;
    }

    private function applyPreset(string $name): void
    {
        $presets = config('ip-info.presets', []);

        if (! isset($presets[$name]) || ! is_array($presets[$name])) {
            $this->warn("Unknown preset [{$name}]. Skipping.");

            return;
        }

        $this->mergePresetIntoConfig($presets[$name]);
        $this->info("Applied preset [{$name}]. Update .env or config/ip-info.php to persist.");
    }

    /**
     * @param  array<string, mixed>  $preset
     */
    private function mergePresetIntoConfig(array $preset): void
    {
        foreach ($preset as $section => $values) {
            if (! is_array($values)) {
                continue;
            }

            $current = config('ip-info.'.$section, []);

            if (! is_array($current)) {
                $current = [];
            }

            config(['ip-info.'.$section => array_replace_recursive($current, $values)]);
        }
    }
}
