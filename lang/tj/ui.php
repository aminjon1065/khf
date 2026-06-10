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
    'common' => [
        'back' => 'Ба қафо',
        'check' => 'Санҷидан',
        'close' => 'Пӯшидан',
        'email' => 'Почтаи электронӣ',
        'emergency_map' => 'Харитаи ҲФ',
        'find' => 'Ҷустуҷӯ',
        'latest_news' => 'Хабарҳои охирин',
        'next' => 'Ба пеш',
        'no_publications' => 'Ҳоло интишорот мавҷуд нест.',
        'operational_situation' => 'Вазъи оперативӣ',
        'reference_number' => 'Рақами бақайдгирӣ',
        'track_status' => 'Пайгирии вазъият',
    ],
    'home' => [
        'meta_title' => 'Саҳифаи асосӣ',
        'hero' => [
            'title' => 'Кумитаи ҳолатҳои фавқулодда ва мудофиаи гражданӣ',
            'subtitle' => 'Маълумоти оперативӣ дар бораи таҳдидҳо ва ҳолатҳои фавқулодда, дастурамалҳои бехатарӣ ва огоҳонии аҳолии Ҷумҳурии Тоҷикистон.',
            'emergency_call' => 'Занги фаврӣ: 112',
        ],
        'quick_links' => [
            'emergency_phone' => 'Телефони фаврӣ',
            'safety_guides_label' => 'Дастурамалҳои бехатарӣ',
            'safety_guides_hint' => 'Тарзи амал ҳангоми ҲФ',
            'subscribe_label' => 'Обуна',
            'subscribe_hint' => 'Огоҳиномаҳо оид ба таҳдидҳо',
        ],
        'news' => [
            'view_all' => 'Ҳамаи хабарҳо →',
        ],
    ],
    'news' => [
        'title' => 'Хабарҳо',
        'heading' => 'Хабарҳо ва маводҳо',
        'back_to_list' => '← Ба рӯйхати хабарҳо',
        'author' => 'Муаллиф: :author',
    ],
    'incidents' => [
        'subtitle' => 'Ҳодисаҳо ва ҳолатҳои фавқулодда',
        'empty' => 'Ҳодисаҳои бақайдгирифташуда мавҷуд нестанд.',
    ],
    'map' => [
        'heading' => 'Харитаи интерактивии ҲФ',
        'subtitle' => 'Ҳодисаҳои фаъол дар қаламрави Ҷумҳурии Тоҷикистон',
    ],
    'documents' => [
        'title' => 'Ҳуҷҷатҳо',
        'empty' => 'Ҳуҷҷатҳо ёфт нашуданд.',
        'form' => [
            'search_placeholder' => 'Ҷустуҷӯ аз рӯи ном…',
            'type_placeholder' => 'Намуд',
            'all_types' => 'Ҳамаи намудҳо',
        ],
    ],
    'appeals' => [
        'title' => 'Қабулгоҳи электронӣ',
        'subtitle' => 'Муроҷиатҳои шаҳрвандон ба Кумитаи ҳолатҳои фавқулодда',
        'track_existing' => 'Пайгирии муроҷиати қаблан пешниҳодшуда',
        'form' => [
            'category' => 'Категория',
            'name' => 'Номи шумо',
            'phone_optional' => 'Телефон (ихтиёрӣ)',
            'subject' => 'Мавзӯъ',
            'message' => 'Паём',
            'submit' => 'Ирсоли муроҷиат',
        ],
        'success' => [
            'title' => 'Муроҷиат қабул шуд',
            'reference_hint' => 'Рақами бақайдгирии шумо барои пайгирии вазъият:',
            'new_appeal' => 'Муроҷиати нав',
        ],
        'track' => [
            'title' => 'Пайгирии муроҷиат',
            'hint' => 'Рақами бақайдгирии муроҷиатро ворид намоед',
            'reference_placeholder' => 'OBR-2026-XXXXXX',
            'not_found' => 'Муроҷиат бо ин рақам ёфт нашуд.',
            'category_label' => 'Категория: :category',
            'submitted_label' => 'Пешниҳод шуд: :created_at',
            'updated_label' => 'Навсозӣ шуд: :updated_at',
        ],
    ],
    'tourism' => [
        'create' => [
            'page_title' => 'Бақайдгирии гурӯҳи сайёҳӣ',
            'subtitle' => 'Барои бехатарии худ хадамоти наҷотдиҳии кӯҳиро дар бораи хатсайр огоҳ намоед',
            'success_page_title' => 'Дархост қабул шуд',
            'success_heading' => 'Дархост ба қайд гирифта шуд',
            'reference_hint' => 'Рақами бақайдгирӣ барои пайгирӣ:',
            'new_application_button' => 'Дархости нав',
            'track_link' => 'Пайгирии дархост',
        ],
        'form' => [
            'leader_name' => 'Роҳбари гурӯҳ',
            'leader_phone' => 'Телефон',
            'leader_email' => 'Почтаи электронӣ (ихтиёрӣ)',
            'participants_count' => 'Шумораи иштирокчиён',
            'region' => 'Минтақа',
            'region_placeholder' => 'Минтақаро интихоб намоед',
            'region_none' => 'Муайян нашудааст',
            'start_date' => 'Санаи баромад',
            'end_date' => 'Санаи бозгашт',
            'route' => 'Хатсайр',
            'equipment' => 'Таҷҳизот ва хусусиятҳо (ихтиёрӣ)',
            'submit' => 'Бақайдгирии гурӯҳ',
            'reference_placeholder' => 'TUR-2026-XXXXXX',
        ],
        'track' => [
            'title' => 'Пайгирии дархост',
            'hint' => 'Рақами бақайдгирии дархости гурӯҳи сайёҳиро ворид намоед',
            'not_found' => 'Дархост бо ин рақам ёфт нашуд.',
            'route' => 'Хатсайр: :route',
        ],
    ],
    'subscribe' => [
        'title' => 'Обуна ба огоҳиномаҳо',
        'subtitle' => 'Огоҳониҳо оид ба ҲФ ва хабарҳоро ба почтаи электронӣ дарёфт намоед',
        'form' => [
            'topics' => 'Мавзӯъҳо',
            'region_optional' => 'Минтақа (ихтиёрӣ)',
            'all_regions' => 'Ҳамаи минтақаҳо',
            'consent' => 'Ман ба коркарди маълумоти шахсӣ ва гирифтани хабарнома розигӣ медиҳам.',
            'submit' => 'Обуна шудан',
        ],
        'status' => [
            'pending' => 'Почтаи электронии худро санҷида, обунаро тасдиқ намоед.',
            'confirmed' => 'Обуна тасдиқ шуд. Ташаккур!',
            'unsubscribed' => 'Шумо обунаи огоҳиномаҳоро бекор кардед.',
            'invalid' => 'Пайванд беэътибор аст ё муҳлати он гузаштааст.',
        ],
    ],
];
