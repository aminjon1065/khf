<?php

declare(strict_types=1);

/*
 * Tajik labels for the application enums (ТЗ §14). Keys are the enum backing values; keep the
 * key sets identical across lang/{tj,ru,en}/enums.php — guarded by InterfaceDictionaryTest.
 */

return [
    'alert_status' => [
        'draft' => 'Сиёҳнавис',
        'published' => 'Нашршуда',
        'cancelled' => 'Бекоршуда',
    ],
    'appeal_category' => [
        'general' => 'Саволи умумӣ',
        'complaint' => 'Шикоят',
        'proposal' => 'Пешниҳод',
        'gratitude' => 'Миннатдорӣ',
    ],
    'appeal_status' => [
        'new' => 'Нав',
        'in_progress' => 'Дар баррасӣ',
        'answered' => 'Ҷавоб дода шуд',
        'closed' => 'Пӯшида',
    ],
    'content_status' => [
        'draft' => 'Сиёҳнавис',
        'moderation' => 'Дар модератсия',
        'published' => 'Нашршуда',
        'archived' => 'Дар бойгонӣ',
    ],
    'guide_audience' => [
        'general' => 'Барои аҳолӣ',
        'children' => 'Барои кӯдакон',
    ],
    'document_type' => [
        'law' => 'Қонунгузорӣ',
        'regulation' => 'Санадҳои меъёрӣ',
        'departmental' => 'Ҳуҷҷатҳои идоравӣ',
        'plan' => 'Нақшаҳо',
        'report' => 'Ҳисоботҳо',
        'form' => 'Шаклҳо ва бланкҳо',
    ],
    'employment_type' => [
        'full_time' => 'Шуғли пурра',
        'part_time' => 'Шуғли қисман',
        'contract' => 'Шартномавӣ',
        'temporary' => 'Муваққатӣ',
    ],
    'hazard_level' => [
        'normal' => 'Меъёр',
        'elevated' => 'Омодабоши баланд',
        'danger' => 'Хатарнок',
        'critical' => 'Хатари фавқулодда',
    ],
    'incident_status' => [
        'active' => 'Фаъол',
        'controlled' => 'Таҳти назорат',
        'resolved' => 'Анҷомёфта',
    ],
    'incident_type' => [
        'earthquake' => 'Заминҷунбӣ',
        'mudflow' => 'Сел ва селоб',
        'flood' => 'Обхезӣ',
        'avalanche' => 'Тарма',
        'landslide' => 'Ярч',
        'fire' => 'Сӯхтор',
        'glof' => 'Рахнашавии кӯлҳои пиряхӣ',
    ],
    'post_type' => [
        'news' => 'Хабар',
        'press_release' => 'Прессрелиз',
        'announcement' => 'Эълон',
        'summary' => 'Хулосаи фаврӣ',
    ],
    'tender_type' => [
        'goods' => 'Молҳо',
        'works' => 'Корҳо',
        'services' => 'Хизматрасониҳо',
        'consulting' => 'Хизматрасониҳои машваратӣ',
    ],
    'role' => [
        'super-admin' => 'Суперадминистратор',
        'moderator' => 'Модератор',
    ],
    'subscription_status' => [
        'pending' => 'Дар интизори тасдиқ',
        'confirmed' => 'Тасдиқшуда',
        'unsubscribed' => 'Лағвшуда',
    ],
    'subscription_topic' => [
        'alerts' => 'Огоҳониҳо дар бораи ҳолатҳои фавқулодда',
        'news' => 'Хабарҳо',
        'announcements' => 'Эълонҳо',
    ],
];
