<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;

final class StarterKitCommand extends Command
{
    protected $signature = 'ip-info:starter
                            {--preset=cloudflare : Preset to apply during install}';

    protected $description = 'Publish config, middleware stub, and run ip-info:install with a preset.';

    public function handle(): int
    {
        $this->info('Publishing Laravel IP Info starter kit...');

        $this->call('vendor:publish', ['--tag' => 'ip-info-middleware']);
        $this->call('ip-info:install', [
            '--preset' => $this->option('preset'),
        ]);

        $this->newLine();
        $this->line('Add to bootstrap/app.php or app/Http/Kernel.php:');
        $this->line('  \\SuprunBohdan\\IpInfo\\Laravel\\Http\\Middleware\\ResolveClientIp::class');
        $this->newLine();
        $this->info('Starter kit published.');

        return self::SUCCESS;
    }
}
