<?php

declare(strict_types=1);

/*
 * Russian labels for the application enums (ТЗ §14). Keys are the enum backing values; keep the
 * key sets identical across lang/{tj,ru,en}/enums.php — guarded by InterfaceDictionaryTest.
 */

return [
    'alert_status' => [
        'draft' => 'Черновик',
        'published' => 'Опубликовано',
        'cancelled' => 'Отменено',
    ],
    'appeal_category' => [
        'general' => 'Общий вопрос',
        'complaint' => 'Жалоба',
        'proposal' => 'Предложение',
        'gratitude' => 'Благодарность',
    ],
    'appeal_status' => [
        'new' => 'Новое',
        'in_progress' => 'В работе',
        'answered' => 'Отвечено',
        'closed' => 'Закрыто',
    ],
    'content_status' => [
        'draft' => 'Черновик',
        'moderation' => 'На модерации',
        'published' => 'Опубликовано',
        'archived' => 'В архиве',
    ],
    'guide_audience' => [
        'general' => 'Для населения',
        'children' => 'Для детей',
    ],
    'document_type' => [
        'law' => 'Законодательство',
        'regulation' => 'Нормативные акты',
        'departmental' => 'Ведомственные документы',
        'plan' => 'Планы',
        'report' => 'Отчёты',
        'form' => 'Формы и бланки',
    ],
    'hazard_level' => [
        'normal' => 'Норма',
        'elevated' => 'Повышенная готовность',
        'danger' => 'Опасно',
        'critical' => 'Чрезвычайная опасность',
    ],
    'incident_status' => [
        'active' => 'Активно',
        'controlled' => 'Под контролем',
        'resolved' => 'Завершено',
    ],
    'incident_type' => [
        'earthquake' => 'Землетрясение',
        'mudflow' => 'Сель и паводок',
        'flood' => 'Наводнение',
        'avalanche' => 'Лавина',
        'landslide' => 'Оползень',
        'fire' => 'Пожар',
        'glof' => 'Прорыв ледниковых озёр',
    ],
    'post_type' => [
        'news' => 'Новость',
        'press_release' => 'Пресс-релиз',
        'announcement' => 'Объявление',
        'summary' => 'Оперативная сводка',
    ],
    'role' => [
        'super-admin' => 'Суперадминистратор',
        'moderator' => 'Модератор',
    ],
    'subscription_status' => [
        'pending' => 'Ожидает подтверждения',
        'confirmed' => 'Подтверждён',
        'unsubscribed' => 'Отписан',
    ],
    'subscription_topic' => [
        'alerts' => 'Оповещения о ЧС',
        'news' => 'Новости',
        'announcements' => 'Объявления',
    ],
];
