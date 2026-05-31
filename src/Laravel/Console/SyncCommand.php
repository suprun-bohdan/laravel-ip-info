<?php

declare(strict_types=1);

namespace SuprunBohdan\IpInfo\Laravel\Console;

use Illuminate\Console\Command;
use SuprunBohdan\IpInfo\Laravel\Sync\IpInfoSyncFixer;
use SuprunBohdan\IpInfo\Laravel\Sync\IpInfoSyncInspector;
use SuprunBohdan\IpInfo\Laravel\Sync\SyncReport;

final class SyncCommand extends Command
{
    protected $signature = 'ip-info:sync
                            {--json : Output sync report as JSON}
                            {--fix : Apply safe fixes (publish missing files, migrate)}
                            {--publish-config : Publish config stub when allowed}
                            {--publish-middleware : Publish middleware stubs when allowed}
                            {--check-routes : Report routes section health only}
                            {--register-middleware : Register ResolveClientIp middleware (opt-in)}
                            {--with-schedule : Append update schedule stubs to routes/console.php}
                            {--force : Allow overwriting outdated published stubs}';

    protected $description = 'Audit Laravel IP Info integration (config, middleware, routes, security).';

    public function __construct(
        private IpInfoSyncInspector $inspector,
        private IpInfoSyncFixer $fixer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $routesOnly = (bool) $this->option('check-routes');
        $report = $this->inspector->inspect($routesOnly);

        if ($this->shouldApplyFixes()) {
            $messages = $this->fixer->apply(
                $this,
                $report,
                (bool) $this->option('publish-config'),
                (bool) $this->option('publish-middleware'),
                (bool) $this->option('fix'),
                (bool) $this->option('force'),
                (bool) $this->option('register-middleware'),
                (bool) $this->option('with-schedule'),
            );

            foreach ($messages as $message) {
                $this->line($message);
            }

            $report = $this->inspector->inspect($routesOnly);
        }

        if ($this->option('json')) {
            $this->line(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return $report->healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->renderHumanReport($report);

        return $report->healthy ? self::SUCCESS : self::FAILURE;
    }

    private function shouldApplyFixes(): bool
    {
        return (bool) $this->option('fix')
            || (bool) $this->option('publish-config')
            || (bool) $this->option('publish-middleware')
            || (bool) $this->option('register-middleware')
            || (bool) $this->option('with-schedule');
    }

    private function renderHumanReport(SyncReport $report): void
    {
        $this->line('Laravel IP Info — application sync');
        $this->newLine();

        $this->table(['Area', 'Status', 'Details'], [
            ['Config', $report->configStatus->value, $report->configPath.($report->configCached ? ' (config cached)' : '')],
            ['Middleware published', $report->middlewarePublishedStatus->value, $report->middlewarePath],
            ['Middleware registered', $report->middlewareRegistered, 'detect-only; see snippets below'],
            ['Routes', $report->routesStatus->value, ($report->routesEnabled ? 'enabled' : 'disabled').' '.$report->routesPath],
            ['Database', $report->databaseEnabled ? ($report->databaseStale ? 'stale' : 'ok') : 'disabled', 'table: '.$report->databaseTable],
            ['Location DB', $report->locationDbEnabled ? ($report->locationDbStale ? 'stale' : 'ok') : 'disabled', 'edition: '.$report->locationDbEdition.', installed: '.($report->locationDbInstalled ? 'yes' : 'no')],
            ['MaxMind', $report->maxmindEnabled ? ($report->maxmindStale ? 'stale' : 'ok') : 'disabled', 'edition: '.$report->maxmindEdition.', installed: '.($report->maxmindInstalled ? 'yes' : 'no')],
            ['Overall', $report->healthy ? 'healthy' : 'needs attention', ''],
        ]);

        if ($report->trustedHeadersWithoutProxyCidrs) {
            $this->warn('Trusted proxy headers are configured without proxy CIDRs.');
        }

        if ($report->insecureHttpDriverActive) {
            $this->warn('Active HTTP driver uses insecure http:// without allow_insecure.');
        }

        if ($report->routesEnabledWithoutMiddleware) {
            $this->warn('Routes are enabled without route middleware protection.');
        }

        if ($report->preset['name'] !== null) {
            $this->newLine();
            $this->line('Preset ['.$report->preset['name'].'] recommendations (report only):');

            foreach ($report->preset['env_recommendations'] as $line) {
                $this->line('  '.$line);
            }
        }

        if ($report->middlewareRegistered !== 'registered') {
            $this->newLine();
            $this->line('Middleware registration snippets:');

            foreach ($report->middlewareSnippets as $label => $snippet) {
                $this->line('['.$label.']');
                $this->line($snippet);
                $this->newLine();
            }
        }

        if ($report->actions !== []) {
            $this->newLine();
            $this->line('Suggested actions:');

            foreach ($report->actions as $action) {
                $this->line('  - '.$action);
            }
        }
    }
}
