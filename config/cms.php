<?php

use App\Enums\Permission;
use App\Models\Alert;
use App\Models\Category;
use App\Models\Document;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GovService;
use App\Models\Guide;
use App\Models\Incident;
use App\Models\Leader;
use App\Models\Page;
use App\Models\Poll;
use App\Models\Post;
use App\Models\Statistic;
use App\Models\Subdivision;
use App\Models\Tag;
use App\Models\Tender;
use App\Models\Vacancy;

return [

    /*
    |--------------------------------------------------------------------------
    | CMS Content Types
    |--------------------------------------------------------------------------
    |
    | Each entry maps to a Statamic-like collection. Features:
    | editorial, translations, revisions, soft_deletes, schedulable, seo, blocks
    |
    */

    'content_types' => [

        'page' => [
            'label' => 'Страницы',
            'model' => Page::class,
            'blueprint' => 'page.default',
            'route_prefix' => 'pages',
            'manage_permission' => Permission::ManagePages->value,
            'view_permission' => Permission::ViewPages->value,
            'icon' => 'file-text',
            'features' => ['editorial', 'translations', 'revisions', 'soft_deletes', 'seo', 'blocks'],
            'sortable' => ['sort_order', 'status', 'created_at', 'updated_at'],
            'default_sort' => 'sort_order',
            'default_sort_direction' => 'asc',
        ],

        'post' => [
            'label' => 'Новости',
            'model' => Post::class,
            'blueprint' => 'post.default',
            'route_prefix' => 'posts',
            'manage_permission' => Permission::ManagePosts->value,
            'view_permission' => Permission::ViewPosts->value,
            'icon' => 'newspaper',
            'features' => ['editorial', 'translations', 'revisions', 'soft_deletes', 'schedulable', 'seo'],
            'sortable' => ['status', 'type', 'published_at', 'created_at'],
            'default_sort' => 'published_at',
            'default_sort_direction' => 'desc',
        ],

        'document' => [
            'label' => 'Документы',
            'model' => Document::class,
            'blueprint' => 'document.default',
            'route_prefix' => 'documents',
            'list_search_field' => 'name',
            'manage_permission' => Permission::ManageDocuments->value,
            'view_permission' => Permission::ViewDocuments->value,
            'icon' => 'file-stack',
            'features' => ['editorial', 'translations', 'revisions', 'soft_deletes'],
            'sortable' => ['type', 'document_date', 'status', 'created_at'],
            'default_sort' => 'document_date',
            'default_sort_direction' => 'desc',
        ],

        'guide' => [
            'label' => 'Памятки',
            'model' => Guide::class,
            'blueprint' => 'guide.default',
            'route_prefix' => 'guides',
            'manage_permission' => Permission::ManageGuides->value,
            'view_permission' => Permission::ViewGuides->value,
            'icon' => 'book-open',
            'features' => ['editorial', 'translations', 'revisions', 'soft_deletes'],
            'sortable' => ['hazard_type', 'audience', 'status', 'sort_order', 'created_at'],
            'default_sort' => 'sort_order',
            'default_sort_direction' => 'asc',
        ],

        'gallery' => [
            'label' => 'Галереи',
            'model' => Gallery::class,
            'blueprint' => 'gallery.default',
            'route_prefix' => 'gallery',
            'manage_permission' => Permission::ManageGallery->value,
            'view_permission' => Permission::ViewGallery->value,
            'icon' => 'images',
            'features' => ['editorial', 'translations'],
            'sortable' => ['created_at'],
            'default_sort' => 'created_at',
            'default_sort_direction' => 'desc',
        ],

        'faq' => [
            'label' => 'FAQ',
            'model' => Faq::class,
            'blueprint' => 'faq.default',
            'route_prefix' => 'faqs',
            'list_search_field' => 'question',
            'manage_permission' => Permission::ManageFaqs->value,
            'view_permission' => Permission::ViewFaqs->value,
            'icon' => 'circle-help',
            'features' => ['editorial', 'translations'],
            'sortable' => ['sort_order', 'created_at'],
            'default_sort' => 'sort_order',
            'default_sort_direction' => 'asc',
        ],

        'poll' => [
            'label' => 'Опросы',
            'model' => Poll::class,
            'blueprint' => 'poll.default',
            'route_prefix' => 'polls',
            'manage_permission' => Permission::ManagePolls->value,
            'view_permission' => Permission::ViewPolls->value,
            'icon' => 'vote',
            'features' => ['editorial', 'translations'],
            'sortable' => ['created_at'],
            'default_sort' => 'created_at',
            'default_sort_direction' => 'desc',
        ],

        'gov_service' => [
            'label' => 'Госуслуги',
            'model' => GovService::class,
            'blueprint' => 'gov_service.default',
            'route_prefix' => 'services',
            'manage_permission' => Permission::ManageServices->value,
            'view_permission' => Permission::ViewServices->value,
            'icon' => 'landmark',
            'features' => ['editorial', 'translations'],
            'sortable' => ['sort_order', 'created_at'],
            'default_sort' => 'sort_order',
            'default_sort_direction' => 'asc',
        ],

        'statistic' => [
            'label' => 'Статистика',
            'model' => Statistic::class,
            'blueprint' => 'statistic.default',
            'route_prefix' => 'statistics',
            'list_search_field' => 'label',
            'manage_permission' => Permission::ManageStatistics->value,
            'view_permission' => Permission::ViewStatistics->value,
            'icon' => 'chart-bar',
            'features' => ['editorial', 'translations'],
            'sortable' => ['sort_order', 'created_at'],
            'default_sort' => 'sort_order',
            'default_sort_direction' => 'asc',
        ],

        'leader' => [
            'label' => 'Руководство',
            'model' => Leader::class,
            'blueprint' => 'leader.default',
            'route_prefix' => 'leadership',
            'list_search_field' => 'full_name',
            'manage_permission' => Permission::ManageLeadership->value,
            'view_permission' => Permission::ViewLeadership->value,
            'icon' => 'users',
            'features' => ['editorial', 'translations'],
            'sortable' => ['sort_order', 'created_at'],
            'default_sort' => 'sort_order',
            'default_sort_direction' => 'asc',
        ],

        'subdivision' => [
            'label' => 'Структура',
            'model' => Subdivision::class,
            'blueprint' => 'subdivision.default',
            'route_prefix' => 'structure',
            'list_search_field' => 'name',
            'manage_permission' => Permission::ManageStructure->value,
            'view_permission' => Permission::ViewStructure->value,
            'icon' => 'network',
            'features' => ['editorial', 'translations'],
            'sortable' => ['sort_order', 'created_at'],
            'default_sort' => 'sort_order',
            'default_sort_direction' => 'asc',
        ],

        'vacancy' => [
            'label' => 'Вакансии',
            'model' => Vacancy::class,
            'blueprint' => 'vacancy.default',
            'route_prefix' => 'vacancies',
            'manage_permission' => Permission::ManageVacancies->value,
            'view_permission' => Permission::ViewVacancies->value,
            'icon' => 'briefcase',
            'features' => ['editorial', 'translations', 'soft_deletes', 'schedulable', 'seo'],
            'sortable' => ['status', 'published_at', 'created_at'],
            'default_sort' => 'published_at',
            'default_sort_direction' => 'desc',
        ],

        'tender' => [
            'label' => 'Тендеры',
            'model' => Tender::class,
            'blueprint' => 'tender.default',
            'route_prefix' => 'tenders',
            'manage_permission' => Permission::ManageTenders->value,
            'view_permission' => Permission::ViewTenders->value,
            'icon' => 'gavel',
            'features' => ['editorial', 'translations', 'soft_deletes', 'schedulable', 'seo'],
            'sortable' => ['status', 'published_at', 'created_at'],
            'default_sort' => 'published_at',
            'default_sort_direction' => 'desc',
        ],

        'incident' => [
            'label' => 'Инциденты',
            'model' => Incident::class,
            'blueprint' => 'incident.default',
            'route_prefix' => 'incidents',
            'manage_permission' => Permission::ManageIncidents->value,
            'view_permission' => Permission::ViewIncidents->value,
            'icon' => 'siren',
            'features' => ['translations', 'soft_deletes'],
            'sortable' => ['status', 'occurred_at', 'created_at'],
            'default_sort' => 'occurred_at',
            'default_sort_direction' => 'desc',
        ],

        'alert' => [
            'label' => 'Оповещения',
            'model' => Alert::class,
            'blueprint' => 'alert.default',
            'route_prefix' => 'alerts',
            'manage_permission' => Permission::ManageAlerts->value,
            'view_permission' => Permission::ViewAlerts->value,
            'icon' => 'bell',
            'features' => ['translations', 'soft_deletes'],
            'sortable' => ['status', 'hazard_level', 'starts_at', 'created_at'],
            'default_sort' => 'starts_at',
            'default_sort_direction' => 'desc',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Published content cache (Stache-like)
    |--------------------------------------------------------------------------
    */

    'content_cache' => [
        'enabled' => env('CMS_CONTENT_CACHE', true),
        'ttl' => (int) env('CMS_CONTENT_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Headless API (collections + globals)
    |--------------------------------------------------------------------------
    */

    'api' => [
        'per_page' => 15,
        'collection_aliases' => [
            'news' => 'post',
        ],
        'scopes' => [
            'incident' => 'active',
            'alert' => 'active',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CMS Globals (site-wide settings)
    |--------------------------------------------------------------------------
    */

    'globals' => [

        'president' => [
            'label' => 'Президент',
            'blueprint' => 'president.default',
            'icon' => 'landmark',
            'fallback' => [
                'url' => env('PRESIDENT_URL', 'https://president.tj'),
                'photo' => env('PRESIDENT_PHOTO', '/images/president.webp'),
            ],
        ],

        'social' => [
            'label' => 'Социальные сети',
            'blueprint' => 'social.default',
            'icon' => 'share-2',
            'fallback' => [
                'telegram' => env('SOCIAL_TELEGRAM_URL'),
                'facebook' => env('SOCIAL_FACEBOOK_URL'),
                'instagram' => env('SOCIAL_INSTAGRAM_URL'),
                'youtube' => env('SOCIAL_YOUTUBE_URL'),
                'x' => env('SOCIAL_X_URL'),
            ],
        ],

        'footer' => [
            'label' => 'Подвал сайта',
            'blueprint' => 'footer.default',
            'icon' => 'panel-bottom',
            'fallback' => [
                'government_url' => env('FOOTER_GOVERNMENT_URL', 'https://government.tj'),
                'egov_url' => env('FOOTER_EGOV_URL', 'https://egov.tj'),
                'hotline' => env('FOOTER_HOTLINE', '112'),
                'copyright' => env('FOOTER_COPYRIGHT'),
                'resource_links' => [],
            ],
        ],

        'seo_defaults' => [
            'label' => 'SEO по умолчанию',
            'blueprint' => 'seo_defaults.default',
            'icon' => 'search',
            'fallback' => [
                'title' => env('SEO_DEFAULT_TITLE'),
                'description' => env('SEO_DEFAULT_DESCRIPTION'),
                'image' => env('SEO_DEFAULT_IMAGE', '/images/emblem-tj.webp'),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Menu builder
    |--------------------------------------------------------------------------
    |
    | Collections whose individual published entries can be linked from menus.
    | Values are public show-route names (must accept locale + slug).
    |
    */

    'menu' => [

        'entry_collections' => [
            'post' => 'news.show',
            'guide' => 'guides.show',
            'gallery' => 'gallery.show',
            'poll' => 'polls.show',
            'gov_service' => 'services.show',
            'vacancy' => 'vacancies.show',
            'tender' => 'tenders.show',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Taxonomies (categories, tags)
    |--------------------------------------------------------------------------
    |
    | Maps taxonomy handles to models and the CMS collections that may use them.
    |
    */

    'taxonomies' => [

        'categories' => [
            'label' => 'Рубрики',
            'model' => Category::class,
            'cardinality' => 'single',
            'field' => 'category_id',
            'collections' => ['post'],
        ],

        'tags' => [
            'label' => 'Теги',
            'model' => Tag::class,
            'cardinality' => 'multiple',
            'field' => 'tag_ids',
            'collections' => ['post', 'page'],
        ],

    ],

];
