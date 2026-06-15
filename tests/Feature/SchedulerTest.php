<?php

it('schedules the database queue to be drained every minute', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('queue:work --stop-when-empty')
        ->assertSuccessful();
});

it('schedules weekly pruning of the audit log', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('activitylog:clean')
        ->assertSuccessful();
});
