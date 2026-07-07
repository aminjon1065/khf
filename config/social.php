<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Official social media profiles (ТЗ §6.12, §15.3)
    |--------------------------------------------------------------------------
    |
    | Perf-safe outbound links only — no third-party embeds or widgets. Leave a
    | URL empty to hide that network from the public footer.
    |
    */

    'links' => [
        'telegram' => env('SOCIAL_TELEGRAM_URL'),
        'facebook' => env('SOCIAL_FACEBOOK_URL'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL'),
        'youtube' => env('SOCIAL_YOUTUBE_URL'),
        'x' => env('SOCIAL_X_URL'),
    ],

];
