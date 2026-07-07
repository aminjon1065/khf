<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deployment environments (ТЗ §16.1)
    |--------------------------------------------------------------------------
    |
    | local — developer workstations (Laragon/Herd/Sail).
    | staging — pre-production UAT mirror (§18.1); same stack as production.
    | production — shared-hosting live site.
    |
    */

    'environments' => ['local', 'staging', 'production', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | Secrets required before a deploy (checked by `deploy:env-check`)
    |--------------------------------------------------------------------------
    |
    | Values must be non-empty and must not match the placeholder patterns below.
    | Generate VAPID keys once per environment: `php artisan webpush:vapid`
    | Encrypt env files at rest: `php artisan env:encrypt --env=production`
    |
    */

    'required_secrets' => [
        'staging' => [
            'APP_KEY',
            'APP_URL',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'VAPID_SUBJECT',
            'VAPID_PUBLIC_KEY',
            'VAPID_PRIVATE_KEY',
            'MAIL_FROM_ADDRESS',
        ],
        'production' => [
            'APP_KEY',
            'APP_URL',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'VAPID_SUBJECT',
            'VAPID_PUBLIC_KEY',
            'VAPID_PRIVATE_KEY',
            'MAIL_FROM_ADDRESS',
            'MAIL_HOST',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholder values that fail env-check (secrets must be replaced)
    |--------------------------------------------------------------------------
    */

    'placeholder_patterns' => [
        '/^$/',
        '/^null$/i',
        '/^changeme$/i',
        '/^your[-_]/i',
        '/^example\.com$/i',
        '/^hello@example\.com$/i',
        '/^secret$/i',
        '/^password$/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Detailed health endpoint token (§16.3 monitoring)
    |--------------------------------------------------------------------------
    |
    | GET /health returns a minimal public payload. Pass this token as
    | `Authorization: Bearer …` or `?token=` for the detailed JSON (DB, cache, queue).
    |
    */

    'health_check_token' => env('HEALTH_CHECK_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Queue alert threshold (failed jobs)
    |--------------------------------------------------------------------------
    */

    'failed_jobs_alert_threshold' => (int) env('HEALTH_FAILED_JOBS_THRESHOLD', 10),

];
