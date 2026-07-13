<?php

namespace App\Console\Commands;

use App\Support\StagingSmokeChecker;
use Illuminate\Console\Command;

class DeploySmokeCommand extends Command
{
    protected $signature = 'deploy:smoke
                            {--base-url= : Base URL for live HTTP checks (defaults to APP_URL)}
                            {--in-process : Hit the app kernel instead of HTTP (CI / local)}
                            {--http : Force live HTTP checks against --base-url / APP_URL}
                            {--insecure : Skip TLS certificate verification (Laragon/Herd .test certs)}';

    protected $description = 'Run post-deploy smoke checks for critical public routes and health (ТЗ §18.1)';

    public function handle(StagingSmokeChecker $checker): int
    {
        $inProcess = $this->option('in-process')
            || (! $this->option('http') && app()->environment(['local', 'testing']));

        $baseUrl = $this->option('base-url') ?: null;
        $insecure = (bool) $this->option('insecure');
        $mode = $inProcess ? 'in-process' : 'http';

        $this->components->info("Running deploy smoke checks ({$mode})…");

        if (! $inProcess) {
            $this->line('  Base URL: '.($baseUrl ?: config('app.url')));

            if ($insecure) {
                $this->components->warn('TLS verification disabled (--insecure).');
            }
        }

        $results = $checker->run(
            inProcess: $inProcess,
            baseUrl: is_string($baseUrl) ? $baseUrl : null,
            insecure: $insecure,
        );

        $rows = array_map(
            fn (array $result): array => [
                $result['ok'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
                $result['status'] ?? '—',
                $result['path'],
                $result['message'],
            ],
            $results,
        );

        $this->table(['Result', 'Status', 'Path', 'Detail'], $rows);

        $failed = count(array_filter($results, fn (array $result): bool => ! $result['ok']));
        $passed = count($results) - $failed;

        if ($failed > 0) {
            $this->components->error("Smoke checks failed: {$failed} failed, {$passed} passed.");

            return self::FAILURE;
        }

        $this->components->info("Smoke checks passed ({$passed}).");

        return self::SUCCESS;
    }
}
