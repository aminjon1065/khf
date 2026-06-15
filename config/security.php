<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers (ТЗ §12.1, §12.2)
    |--------------------------------------------------------------------------
    |
    | Tunable security-header policy applied by App\Http\Middleware\SecurityHeaders.
    | Kept in config (not code) so it can be adjusted per environment. CSP is
    | hardened with nonces later in Phase 16.
    |
    */

    'frame_options' => 'SAMEORIGIN',

    'referrer_policy' => 'strict-origin-when-cross-origin',

    // Disable powerful features the portal does not use; geolocation is allowed for the
    // opt-in map locating feature (ТЗ §6.3).
    'permissions_policy' => 'camera=(), microphone=(), payment=(), usb=(), magnetometer=(), geolocation=(self)',

    'hsts' => [
        'enabled' => env('SECURITY_HSTS', true),
        'max_age' => 31_536_000, // 1 year
        'include_subdomains' => true,
        'preload' => false,
    ],

    'csp' => [
        'enabled' => env('SECURITY_CSP', true),

        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            // MapLibre GL fetches OSM raster tiles via fetch() (connect-src) and runs its renderer in
            // a blob: web worker (worker-src) — both must be allowed for the incident map (ТЗ §6.3).
            'connect-src' => ["'self'", 'https://tile.openstreetmap.org', 'https://*.tile.openstreetmap.org'],
            'worker-src' => ["'self'", 'blob:'],
            'frame-ancestors' => ["'self'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'object-src' => ["'none'"],
        ],
    ],

];
