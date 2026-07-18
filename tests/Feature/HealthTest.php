<?php

use App\Support\HealthReporter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('returns public readiness statuses without diagnostic details', function () {
    $this->get(route('health'))
        ->assertSuccessful()
        ->assertJsonPath('status', 'ok')
        ->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database' => ['status'],
                'cache' => ['status'],
                'queue' => ['status'],
                'scheduler' => ['status'],
            ],
        ])
        ->assertJsonMissingPath('environment')
        ->assertJsonMissingPath('checks.database.message');
});

it('returns detailed health checks when the token matches', function () {
    config(['deployment.health_check_token' => 'test-health-token']);

    $this->withToken('test-health-token')
        ->getJson(route('health'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'status',
            'environment',
            'timestamp',
            'checks' => ['database', 'cache', 'queue', 'scheduler'],
        ]);
});

it('rejects an invalid bearer token', function () {
    config(['deployment.health_check_token' => 'valid-token']);

    $this->withToken('wrong')
        ->getJson(route('health'))
        ->assertUnauthorized();
});

it('reports degraded status when failed jobs exceed the threshold', function () {
    config([
        'deployment.health_check_token' => 'ops-token',
        'deployment.failed_jobs_alert_threshold' => 1,
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) str()->uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'test',
        'failed_at' => now(),
    ]);

    $this->withToken('ops-token')
        ->getJson(route('health'))
        ->assertStatus(503)
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.queue.status', 'fail');
});

it('returns a degraded public readiness response when a dependency fails', function () {
    config(['deployment.failed_jobs_alert_threshold' => 1]);

    DB::table('failed_jobs')->insert([
        'uuid' => (string) str()->uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'test',
        'failed_at' => now(),
    ]);

    $this->getJson(route('health'))
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.queue.status', 'fail')
        ->assertJsonMissingPath('checks.queue.message');
});

it('detects a stale scheduler heartbeat in production', function () {
    app()['env'] = 'production';
    config([
        'deployment.health_check_token' => 'ops-token',
        'deployment.scheduler_heartbeat_max_age' => 120,
    ]);
    Cache::forever(
        HealthReporter::SCHEDULER_HEARTBEAT_CACHE_KEY,
        now()->subMinutes(5)->timestamp,
    );

    $this->withToken('ops-token')
        ->getJson(route('health'))
        ->assertServiceUnavailable()
        ->assertJsonPath('checks.scheduler.status', 'fail');
});
