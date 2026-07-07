<?php

use Illuminate\Support\Facades\DB;

it('returns a public health summary without a token', function () {
    $this->get(route('health'))
        ->assertSuccessful()
        ->assertJson([
            'status' => 'ok',
            'environment' => 'testing',
        ])
        ->assertJsonMissing(['checks']);
});

it('returns detailed health checks when the token matches', function () {
    config(['deployment.health_check_token' => 'test-health-token']);

    $this->get(route('health', ['token' => 'test-health-token']))
        ->assertSuccessful()
        ->assertJsonStructure([
            'status',
            'environment',
            'timestamp',
            'checks' => ['database', 'cache', 'queue'],
        ]);
});

it('rejects an invalid health check token', function () {
    config(['deployment.health_check_token' => 'valid-token']);

    $this->get(route('health', ['token' => 'wrong']))
        ->assertSuccessful()
        ->assertJsonMissing(['checks']);
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

    $this->get(route('health', ['token' => 'ops-token']))
        ->assertStatus(503)
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.queue.status', 'fail');
});
