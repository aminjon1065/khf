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
 * database queue by running the worker until it is empty (capped under a minute, non-overlapping),
 * refreshes the alert banner on scheduled window transitions, and prunes the audit log + failed
 * jobs so the database stays bounded.
 *
 * `--queue=alerts,default` makes emergency-alert delivery (ТЗ §6.4, §13) strictly preempt bulk
 * digests and image conversions: the worker empties the `alerts` queue before touching `default`.
 */
Schedule::command('queue:work --queue=alerts,default --stop-when-empty --tries=3 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping();

// Surface scheduled alert window open/close transitions within one cron tick (ТЗ §6.4.1).
Schedule::command('alerts:refresh-cache')
    ->everyMinute()
    ->withoutOverlapping();

// `--force` is mandatory: without it the prune prompts for confirmation and never runs unattended,
// so `activity_log` would grow unbounded on a 24/7 host (ТЗ §4.5, §16.3).
Schedule::command('activitylog:clean --force')
    ->weekly();

// Keep the database-backed failed-job table bounded after a ЧС mass-send (ТЗ §16.3).
Schedule::command('queue:prune-failed --hours=168')
    ->weekly();
