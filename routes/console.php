<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Scheduled tasks (ТЗ §16.1, D-10). On shared hosting a single system cron entry runs
 * `php artisan schedule:run` every minute — no Supervisor or Redis. The scheduler drains the
 * database queue (subscriber notifications, alert dispatch, image conversions) by running the worker
 * until the queue is empty (capped under a minute, non-overlapping), and prunes the audit log weekly
 * so `activity_log` stays bounded.
 */
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('activitylog:clean')
    ->weekly();
