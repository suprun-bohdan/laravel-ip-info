<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use SuprunBohdan\IpInfo\Laravel\Support\CloudflareCidrFetcher;

final class RefreshCloudflareCidrsCommand extends Command
{
    protected $signature = 'ip-info:refresh-cloudflare-cidrs
                            {--dry-run : Print CIDRs without writing}
                            {--json : Machine-readable output}
                            {--write-env-snippet : Print .env line only}';

    protected $description = 'Fetch current Cloudflare egress CIDR ranges for trusted proxy configuration.';

    public function handle(CloudflareCidrFetcher $fetcher): int
    {
        try {
            $cidrs = $fetcher->fetchAll();
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($cidrs === []) {
            $this->error('No Cloudflare CIDR ranges were returned.');

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'cidrs' => $cidrs,
                'env' => $fetcher->toEnvSnippet($cidrs),
                'count' => count($cidrs),
            ], JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        if ($this->option('write-env-snippet')) {
            $this->line($fetcher->toEnvSnippet($cidrs));

            return self::SUCCESS;
        }

        $this->info('Fetched '.count($cidrs).' Cloudflare CIDR ranges.');

        if ($this->option('dry-run')) {
            foreach ($cidrs as $cidr) {
                $this->line($cidr);
            }
        }

        $this->newLine();
        $this->line('Add to .env:');
        $this->line($fetcher->toEnvSnippet($cidrs));

        return self::SUCCESS;
    }
}
