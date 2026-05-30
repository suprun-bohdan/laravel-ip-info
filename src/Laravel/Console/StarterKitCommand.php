<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;

final class StarterKitCommand extends Command
{
    protected $signature = 'ip-info:starter
                            {--preset=cloudflare : Preset to apply during install}
                            {--quick : Enable HTTP geo via quick_start preset}
                            {--register-middleware : Register ResolveClientIp middleware}
                            {--with-schedule : Append update schedule stubs}';

    protected $description = 'Publish config, middleware stub, and run ip-info:install with a preset.';

    public function handle(): int
    {
        $this->info('Publishing Laravel IP Info starter kit...');

        $this->call('vendor:publish', ['--tag' => 'ip-info-middleware']);

        $installOptions = [];

        if ($this->option('quick')) {
            $installOptions['--quick'] = true;
        } else {
            $installOptions['--preset'] = $this->option('preset');
        }

        if ($this->option('register-middleware')) {
            $installOptions['--register-middleware'] = true;
            $installOptions['--force'] = true;
        }

        if ($this->option('with-schedule')) {
            $installOptions['--with-schedule'] = true;
        }

        $this->call('ip-info:install', $installOptions);

        $this->newLine();
        $this->call('ip-info:sync');

        $this->info('Starter kit published.');

        return self::SUCCESS;
    }
}
