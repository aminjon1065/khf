<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

/**
 * @return list<array{id: string, type: string, data: array<string, mixed>}>
 */
function allBlockTypesPayload(): array
{
    return [
        [
            'id' => 'blk-text',
            'type' => 'text',
            'data' => ['content' => '<p>Rich text</p>'],
        ],
        [
            'id' => 'blk-gallery',
            'type' => 'image_gallery',
            'data' => [
                'images' => [
                    ['url' => 'https://example.com/photo.jpg', 'alt' => 'Photo', 'caption' => 'Caption'],
                ],
            ],
        ],
        [
            'id' => 'blk-news',
            'type' => 'news_list',
            'data' => ['count' => '3'],
        ],
        [
            'id' => 'blk-map',
            'type' => 'map_widget',
            'data' => ['lat' => '38.5598', 'lng' => '68.7870', 'zoom' => '10', 'title' => 'Dushanbe'],
        ],
        [
            'id' => 'blk-cta',
            'type' => 'cta',
            'data' => ['label' => 'Learn more', 'url' => '/tj/contacts'],
        ],
        [
            'id' => 'blk-accordion',
            'type' => 'accordion',
            'data' => [
                'items' => [
                    ['title' => 'Question', 'content' => '<p>Answer</p>'],
                ],
            ],
        ],
        [
            'id' => 'blk-table',
            'type' => 'table',
            'data' => [
                'caption' => 'Schedule',
                'headers' => ['Day', 'Hours'],
                'rows' => [['Mon', '09:00']],
            ],
        ],
        [
            'id' => 'blk-contacts',
            'type' => 'contacts',
            'data' => [
                'heading' => 'Office',
                'address' => 'Dushanbe',
                'phone' => '+992 37 123 45 67',
                'email' => 'info@example.com',
                'hours' => 'Mon–Fri 09:00–18:00',
            ],
        ],
    ];
}

function pageBlocksPayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'parent_id' => null,
        'sort_order' => 0,
        'is_home' => false,
        'translations' => [
            'tj' => [
                'title' => 'Саҳифаи блокҳо',
                'slug' => 'sahifai-blokho',
                'content' => '',
                'blocks' => allBlockTypesPayload(),
            ],
            'ru' => [
                'title' => 'Страница блоков',
                'slug' => 'stranica-blokov',
                'content' => '',
                'blocks' => allBlockTypesPayload(),
            ],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('stores all eight block types when saving a page', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.pages.store'), pageBlocksPayload())
        ->assertRedirect(route('admin.pages.index'));

    $blocks = Page::first()->translation('ru')->blocks;

    expect($blocks)->toHaveCount(8)
        ->and(collect($blocks)->pluck('type')->all())->toEqual([
            'text',
            'image_gallery',
            'news_list',
            'map_widget',
            'cta',
            'accordion',
            'table',
            'contacts',
        ]);
});

it('sanitizes html and unsafe urls inside blocks on save', function () {
    $payload = pageBlocksPayload();
    $payload['translations']['tj']['blocks'] = [
        [
            'id' => 'xss-text',
            'type' => 'text',
            'data' => ['content' => '<p>Safe</p><script>alert(1)</script>'],
        ],
        [
            'id' => 'xss-cta',
            'type' => 'cta',
            'data' => ['label' => 'Click', 'url' => 'javascript:alert(1)'],
        ],
        [
            'id' => 'xss-accordion',
            'type' => 'accordion',
            'data' => [
                'items' => [
                    [
                        'title' => '<b>Title</b>',
                        'content' => '<p>Answer</p><script>hack()</script>',
                    ],
                ],
            ],
        ],
    ];

    $this->actingAs($this->editor)
        ->post(route('admin.pages.store'), $payload)
        ->assertRedirect(route('admin.pages.index'));

    $blocks = Page::first()->translation('tj')->blocks;

    expect($blocks[0]['data']['content'])
        ->toContain('<p>Safe</p>')
        ->not->toContain('<script>')
        ->and($blocks[1]['data']['url'])->toBe('#')
        ->and($blocks[2]['data']['items'][0]['title'])->toBe('Title')
        ->and($blocks[2]['data']['items'][0]['content'])->not->toContain('<script>');
});

it('renders a published page with all block types on the public site', function () {
    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->upsertTranslations([
        'ru' => [
            'title' => 'Блоки',
            'slug' => 'bloki-public',
            'content' => null,
            'blocks' => allBlockTypesPayload(),
        ],
    ]);

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'bloki-public']))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('public/pages/show')
            ->where('page.title', 'Блоки')
            ->has('page.blocks', 8)
            ->where('page.blocks.0.type', 'text')
            ->where('page.blocks.1.type', 'image_gallery')
            ->where('page.blocks.2.type', 'news_list')
            ->where('page.blocks.3.type', 'map_widget')
            ->where('page.blocks.3.data.lat', '38.5598')
            ->where('page.blocks.4.type', 'cta')
            ->where('page.blocks.5.type', 'accordion')
            ->where('page.blocks.6.type', 'table')
            ->where('page.blocks.7.type', 'contacts')
        );
});

it('serves homepage blocks from the is_home page', function () {
    $home = Page::factory()->create([
        'status' => ContentStatus::Published,
        'is_home' => true,
    ]);
    $home->upsertTranslations([
        'ru' => [
            'title' => 'Главная',
            'slug' => 'glavnaya-bloki',
            'content' => null,
            'blocks' => [
                [
                    'id' => 'home-cta',
                    'type' => 'cta',
                    'data' => ['label' => 'Контакты', 'url' => '/ru/contacts'],
                ],
            ],
        ],
    ]);

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('public/home')
            ->has('blocks', 1)
            ->where('blocks.0.type', 'cta')
            ->where('blocks.0.data.label', 'Контакты')
        );
});
