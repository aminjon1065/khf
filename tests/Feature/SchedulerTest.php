<?php

it('records a scheduler heartbeat every minute', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('health:scheduler-heartbeat')
        ->assertSuccessful();
});

it('drains the alerts queue before the default queue every minute', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('queue:work --queue=alerts,default --stop-when-empty')
        ->assertSuccessful();
});

it('refreshes the alert banner cache every minute', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('alerts:refresh-cache')
        ->assertSuccessful();
});

it('schedules weekly pruning of the audit log with --force', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('activitylog:clean --force')
        ->assertSuccessful();
});

it('schedules weekly pruning of failed jobs', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('queue:prune-failed')
        ->assertSuccessful();
});
