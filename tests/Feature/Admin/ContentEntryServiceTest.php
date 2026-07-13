<?php

use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;
use App\Models\Faq;
use App\Models\Statistic;
use App\Models\User;
use App\Services\Admin\ContentEntryService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('splits root attributes from blueprint fillable fields', function () {
    $type = app(ContentTypeRegistry::class)->get('statistic');
    $service = app(ContentEntryService::class);

    $attributes = $service->rootAttributes($type, [
        'status' => 'published',
        'value' => '42',
        'year' => '',
        'sort_order' => null,
        'translations' => [
            'ru' => ['label' => 'Ignored here'],
        ],
    ]);

    expect($attributes)
        ->toHaveKeys(['status', 'value', 'year', 'sort_order'])
        ->and($attributes['status'])->toBe(ContentStatus::Published)
        ->and($attributes['value'])->toBe('42')
        ->and($attributes['year'])->toBeNull()
        ->and($attributes['sort_order'])->toBe(0)
        ->and($attributes)->not->toHaveKey('translations');
});

it('filters empty locales and sanitizes rich_text translation fields', function () {
    $type = app(ContentTypeRegistry::class)->get('faq');
    $service = app(ContentEntryService::class);

    $payload = $service->translationsPayload($type, [
        'translations' => [
            'tj' => [
                'question' => 'Чӣ гуна?',
                'answer' => '<p>Safe</p><script>alert(1)</script>',
            ],
            'ru' => [
                'question' => '',
                'answer' => '<p>Skip</p>',
            ],
            'en' => [
                'question' => 'How?',
                'answer' => '<p>Ok</p>',
            ],
        ],
    ]);

    expect($payload)->toHaveKeys(['tj', 'en'])
        ->and($payload)->not->toHaveKey('ru')
        ->and($payload['tj']['question'])->toBe('Чӣ гуна?')
        ->and($payload['tj']['answer'])->toContain('<p>Safe</p>')
        ->and($payload['tj']['answer'])->not->toContain('<script');
});

it('stores and serializes a statistic entry', function () {
    $service = app(ContentEntryService::class);

    $statistic = $service->store('statistic', [
        'status' => 'published',
        'value' => '100',
        'year' => 2026,
        'sort_order' => 3,
        'translations' => [
            'ru' => ['label' => 'Спасённых', 'unit' => 'чел.'],
            'en' => ['label' => '', 'unit' => ''],
        ],
    ]);

    expect($statistic)->toBeInstanceOf(Statistic::class)
        ->and($statistic->value)->toBe('100')
        ->and($statistic->year)->toBe(2026)
        ->and($statistic->translations)->toHaveCount(1);

    $array = $service->entryArray($statistic->fresh(['translations']), 'statistic');

    expect($array)
        ->toHaveKey('id')
        ->and($array['status'])->toBe('published')
        ->and($array['translations']['ru']['label'])->toBe('Спасённых');
});

it('stores a faq with a revision snapshot', function () {
    $service = app(ContentEntryService::class);

    $faq = $service->store('faq', [
        'status' => 'published',
        'sort_order' => 1,
        'translations' => [
            'tj' => [
                'question' => 'Савол?',
                'answer' => '<p>Ҷавоб</p>',
            ],
        ],
    ]);

    expect($faq)->toBeInstanceOf(Faq::class)
        ->and($faq->revisions)->toHaveCount(1)
        ->and($faq->translation('tj')?->question)->toBe('Савол?');
});

it('auto-generates slugs and defaults toggle fields for gov services', function () {
    $service = app(ContentEntryService::class);
    $type = app(ContentTypeRegistry::class)->get('gov_service');

    $attributes = $service->rootAttributes($type, [
        'status' => 'published',
        'category' => 'information',
    ]);

    expect($attributes['is_online'])->toBeFalse()
        ->and($attributes['sort_order'])->toBe(0);

    $payload = $service->translationsPayload($type, [
        'translations' => [
            'ru' => [
                'title' => 'Справка',
                'slug' => '',
                'description' => '<p>Текст</p><script>x</script>',
            ],
        ],
    ]);

    expect($payload['ru']['slug'])->toBe(Str::tajikSlug('Справка').'-ru')
        ->and($payload['ru']['description'])->not->toContain('<script');
});

it('ensures unique guide slugs and nulls empty select values', function () {
    $service = app(ContentEntryService::class);
    $type = app(ContentTypeRegistry::class)->get('guide');

    $first = $service->store('guide', [
        'status' => 'published',
        'audience' => 'general',
        'hazard_type' => '',
        'sort_order' => 0,
        'translations' => [
            'ru' => [
                'title' => 'Памятка',
                'slug' => 'pamyatka',
                'content' => '<p>Ok</p>',
            ],
        ],
    ]);

    expect($first->hazard_type)->toBeNull();

    $payload = $service->translationsPayload($type, [
        'translations' => [
            'ru' => [
                'title' => 'Другая',
                'slug' => 'pamyatka',
                'content' => '<p>Ok</p>',
            ],
        ],
    ]);

    expect($payload['ru']['slug'])->toBe('pamyatka-2');
});

it('normalizes publication schedule and accepts created_by extras for vacancies', function () {
    $service = app(ContentEntryService::class);
    $creator = User::factory()->create();

    $vacancy = $service->store('vacancy', [
        'employment_type' => 'full_time',
        'status' => 'published',
        'positions_count' => 1,
        'published_at' => null,
        'deadline_at' => '2026-12-31',
        'translations' => [
            'ru' => [
                'title' => 'Специалист',
                'slug' => 'specialist',
                'description' => '<p>Текст</p><script>x</script>',
            ],
        ],
    ], [
        'created_by' => $creator->id,
    ]);

    expect($vacancy->created_by)->toBe($creator->id)
        ->and($vacancy->published_at)->not->toBeNull()
        ->and($vacancy->translation('ru')?->description)->not->toContain('<script');

    $array = $service->entryArray($vacancy->fresh(['translations']), 'vacancy');

    expect($array['published_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/')
        ->and($array['deadline_at'])->toMatch('/^\d{4}-\d{2}-\d{2}/');
});

it('sanitizes page blocks and skips missing root fields on partial update', function () {
    $service = app(ContentEntryService::class);
    $type = app(ContentTypeRegistry::class)->get('page');

    $page = $service->store('page', [
        'status' => 'draft',
        'sort_order' => 5,
        'is_home' => false,
        'translations' => [
            'ru' => [
                'title' => 'Страница',
                'slug' => 'stranica',
                'content' => '<p>Ok</p>',
                'blocks' => [
                    [
                        'id' => 'blk-1',
                        'type' => 'text',
                        'data' => ['content' => '<p>Safe</p><script>alert(1)</script>'],
                    ],
                ],
            ],
        ],
    ], saveRevision: false);

    expect($page->sort_order)->toBe(5)
        ->and($page->translation('ru')?->blocks[0]['data']['content'])->toContain('<p>Safe</p>')
        ->and($page->translation('ru')?->blocks[0]['data']['content'])->not->toContain('<script');

    $service->update('page', $page, [
        'translations' => [
            'ru' => [
                'title' => 'Страница 2',
                'slug' => 'stranica',
                'content' => '<p>Ok</p>',
                'blocks' => [],
            ],
        ],
    ], saveRevision: false);

    expect($page->fresh()->sort_order)->toBe(5)
        ->and($page->fresh()->status)->toBe(ContentStatus::Draft);

    $partial = $service->rootAttributes($type, [
        'sort_order' => 9,
    ], withDefaults: false);

    expect($partial)->toBe(['sort_order' => 9]);
});
