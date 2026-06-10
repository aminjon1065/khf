<?php

declare(strict_types=1);

/*
 * English interface dictionary shared with the React front end (ТЗ §14). Keep keys identical
 * across lang/{tj,ru,en}/ui.php; the client `t()` helper falls back to the key when a string
 * is missing.
 */

return [
    'site' => [
        'short_name' => 'CoES',
        'full_name' => 'Committee of Emergency Situations and Civil Defense under the Government of the Republic of Tajikistan',
    ],
    'nav' => [
        'home' => 'Home',
        'news' => 'News',
        'situation' => 'Situation',
        'map' => 'Map',
        'documents' => 'Documents',
        'reception' => 'Public Reception',
        'tourism' => 'Tourism',
        'subscribe' => 'Subscribe',
        'login' => 'Log in',
    ],
    'footer' => [
        'hotline' => 'Unified helpline',
    ],
    'lang' => [
        'switch' => 'Change language',
    ],
];
