<?php

/*
|--------------------------------------------------------------------------
| Legacy / deploy-time redirects (ТЗ §15.1)
|--------------------------------------------------------------------------
|
| Merged with DB-managed redirects from /admin/redirects via RedirectResolver.
| DB entries win on conflict. Prefer CSV import for bulk legacy maps:
|
|   php artisan redirects:import database/data/legacy-redirects.example.csv
|
| Keys are paths without a leading slash (or with — both normalize).
| Values are absolute paths or full URLs to redirect to.
|
*/

return [
    // 'tj/node/123' => '/tj/news/old-post-slug',
    // 'ru/about-us' => '/ru/pages/about',
];
