<?php

declare(strict_types=1);

/*
 * Russian interface dictionary shared with the React front end (ТЗ §14). Keep keys identical
 * across lang/{tj,ru,en}/ui.php; the client `t()` helper falls back to the key when a string
 * is missing.
 */

return [
    'site' => [
        'short_name' => 'КЧС',
        'full_name' => 'Комитет по чрезвычайным ситуациям и гражданской обороне при Правительстве Республики Таджикистан',
    ],
    'nav' => [
        'home' => 'Главная',
        'news' => 'Новости',
        'situation' => 'Обстановка',
        'map' => 'Карта',
        'documents' => 'Документы',
        'reception' => 'Приёмная',
        'tourism' => 'Туризм',
        'subscribe' => 'Подписка',
        'login' => 'Войти',
    ],
    'footer' => [
        'hotline' => 'Единый телефон доверия',
    ],
    'lang' => [
        'switch' => 'Сменить язык',
    ],
];
