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
    /**
     * @return array{status: string, environment: string, timestamp: string}
     */
    public function summary(): array
    {
        return [
            'status' => 'ok',
            'environment' => (string) config('app.env'),
            'timestamp' => now()->toIso8601String(),
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
            $threshold = (int) config('deployment.failed_jobs_alert_threshold', 10);

            if ($failed >= $threshold) {
                return [
                    'status' => 'fail',
                    'value' => $failed,
                    'message' => "Failed jobs ({$failed}) exceed threshold ({$threshold})",
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
}
