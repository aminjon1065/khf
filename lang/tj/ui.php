<?php

declare(strict_types=1);

/*
 * Tajik interface dictionary shared with the React front end (ТЗ §14). Keep keys identical
 * across lang/{tj,ru,en}/ui.php; the client `t()` helper falls back to the key when a string
 * is missing.
 */

return [
    'site' => [
        'short_name' => 'КҲФ',
        'full_name' => 'Кумитаи ҳолатҳои фавқулодда ва мудофиаи граждании назди Ҳукумати Ҷумҳурии Тоҷикистон',
    ],
    'nav' => [
        'home' => 'Асосӣ',
        'news' => 'Хабарҳо',
        'situation' => 'Вазъият',
        'map' => 'Харита',
        'documents' => 'Ҳуҷҷатҳо',
        'reception' => 'Қабулгоҳ',
        'tourism' => 'Сайёҳӣ',
        'subscribe' => 'Обуна',
        'login' => 'Ворид шудан',
    ],
    'footer' => [
        'hotline' => 'Телефони ягонаи боварӣ',
    ],
    'lang' => [
        'switch' => 'Тағйири забон',
    ],
];
