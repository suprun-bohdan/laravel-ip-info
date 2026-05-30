<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use SuprunBohdan\IpInfo\Whois\WhoisLookupService;

final class WhoisLookupCommand extends Command
{
    protected $signature = 'ip-info:whois {ip : Public IP address to query via WHOIS}';

    protected $description = 'Perform a live WHOIS lookup (IANA referral + RIR) for a public IP address.';

    public function handle(WhoisLookupService $whois): int
    {
        $ip = (string) $this->argument('ip');
        $record = $whois->lookup($ip, force: true);

        if ($record === null) {
            $this->error('WHOIS lookup returned no record. Check IP_INFO_WHOIS_ENABLED and that the IP is public.');

            return self::FAILURE;
        }

        foreach ($record->toArray() as $key => $value) {
            if (is_array($value)) {
                $this->line(sprintf('%s: %s', $key, implode(' | ', $value)));

                continue;
            }

            $this->line(sprintf('%s: %s', $key, (string) $value));
        }

        return self::SUCCESS;
    }
}
