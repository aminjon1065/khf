<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Matomo analytics (ТЗ §15.2)
    |--------------------------------------------------------------------------
    |
    | Pageviews are tracked in app.blade.php and on Inertia navigations. Goal IDs are
    | configured in the Matomo admin UI and mapped here for conversion events
    | (appeals, tourist-group registrations, email subscriptions).
    |
    */

    'url' => env('MATOMO_URL'),

    'site_id' => env('MATOMO_SITE_ID'),

    'goals' => [
        'appeal' => env('MATOMO_GOAL_APPEAL'),
        'tourist_group' => env('MATOMO_GOAL_TOURIST_GROUP'),
        'subscription' => env('MATOMO_GOAL_SUBSCRIPTION'),
    ],

];
