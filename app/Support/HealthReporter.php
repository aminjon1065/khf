<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Builds health-check payloads for uptime monitoring (ТЗ §16.3).
 */
class HealthReporter
{
    public const SCHEDULER_HEARTBEAT_CACHE_KEY = 'health:scheduler-heartbeat';

    /**
     * @return array{status: string, timestamp: string, checks: array<string, array{status: string}>}
     */
    public function summary(): array
    {
        $report = $this->detailed();

        return [
            'status' => $report['status'],
            'timestamp' => $report['timestamp'],
            'checks' => collect($report['checks'])
                ->map(fn (array $check): array => ['status' => $check['status']])
                ->all(),
        ];
    }

    /**
     * @return array{status: string, environment: string, timestamp: string, checks: array<string, array{status: string, message?: string, value?: int|string}>}
     */
    public function detailed(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'scheduler' => $this->checkScheduler(),
        ];

        $hasFailure = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'fail');

        return [
            'status' => $hasFailure ? 'degraded' : 'ok',
            'environment' => (string) config('app.env'),
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return ['status' => 'fail', 'message' => 'Database unreachable'];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkCache(): array
    {
        try {
            $key = 'health.ping.'.uniqid('', true);
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== 'ok') {
                return ['status' => 'fail', 'message' => 'Cache read/write failed'];
            }

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return ['status' => 'fail', 'message' => 'Cache unreachable'];
        }
    }

    /**
     * @return array{status: string, value?: int, message?: string}
     */
    private function checkQueue(): array
    {
        try {
            $connection = (string) config('queue.default');
            $size = Queue::connection($connection)->size();
            $failed = (int) DB::table('failed_jobs')->count();
            $failedThreshold = (int) config('deployment.failed_jobs_alert_threshold', 10);
            $pendingThreshold = (int) config('deployment.pending_jobs_alert_threshold', 1000);

            if ($failed >= $failedThreshold) {
                return [
                    'status' => 'fail',
                    'value' => $failed,
                    'message' => "Failed jobs ({$failed}) exceed threshold ({$failedThreshold})",
                ];
            }

            if ($size >= $pendingThreshold) {
                return [
                    'status' => 'fail',
                    'value' => $size,
                    'message' => "Pending jobs ({$size}) exceed threshold ({$pendingThreshold})",
                ];
            }

            return [
                'status' => 'ok',
                'value' => $size,
                'message' => "Pending: {$size}, failed: {$failed}",
            ];
        } catch (Throwable $exception) {
            return ['status' => 'fail', 'message' => 'Queue check failed'];
        }
    }

    /**
     * @return array{status: string, value?: int, message?: string}
     */
    private function checkScheduler(): array
    {
        try {
            $heartbeat = Cache::get(self::SCHEDULER_HEARTBEAT_CACHE_KEY);

            if (! is_numeric($heartbeat)) {
                if (! app()->environment('staging', 'production')) {
                    return ['status' => 'ok', 'message' => 'Heartbeat is only required outside local/testing'];
                }

                return ['status' => 'fail', 'message' => 'Scheduler heartbeat is missing'];
            }

            $age = max(0, now()->timestamp - (int) $heartbeat);
            $maxAge = (int) config('deployment.scheduler_heartbeat_max_age', 180);

            if ($age > $maxAge) {
                return [
                    'status' => 'fail',
                    'value' => $age,
                    'message' => "Scheduler heartbeat is {$age}s old (maximum {$maxAge}s)",
                ];
            }

            return [
                'status' => 'ok',
                'value' => $age,
                'message' => "Heartbeat age: {$age}s",
            ];
        } catch (Throwable) {
            return ['status' => 'fail', 'message' => 'Scheduler heartbeat check failed'];
        }
    }
}
