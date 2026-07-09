<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\ApiToken;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, LanguageSeeder::class]);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function pageTagPayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'parent_id' => null,
        'sort_order' => 0,
        'tag_ids' => [],
        'translations' => [
            'tj' => ['title' => 'Саҳифа', 'slug' => 'sahifa', 'content' => 'Матн'],
            'ru' => ['title' => 'Страница', 'slug' => 'stranitsa', 'content' => 'Текст'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

function pageTagBearer(): array
{
    $plainText = ApiToken::generate('page-tags')['plainText'];

    return ['Authorization' => 'Bearer '.$plainText];
}

it('creates a page with tags', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations([
        'ru' => ['name' => 'О нас', 'slug' => 'o-nas'],
    ]);

    $this->actingAs($this->editor)
        ->post(route('admin.pages.store'), pageTagPayload([
            'tag_ids' => [$tag->id],
        ]))
        ->assertRedirect(route('admin.pages.index'));

    $page = Page::with('tags')->first();

    expect($page->tags)->toHaveCount(1)
        ->and($page->tags->first()->id)->toBe($tag->id);
});

it('syncs tags when updating a page', function () {
    $first = Tag::factory()->create();
    $first->upsertTranslations(['ru' => ['name' => 'Первый', 'slug' => 'pervyy']]);
    $second = Tag::factory()->create();
    $second->upsertTranslations(['ru' => ['name' => 'Второй', 'slug' => 'vtoroy']]);

    $page = Page::factory()->create(['status' => ContentStatus::Draft]);
    $page->upsertTranslations([
        'ru' => ['title' => 'Черновик', 'slug' => 'draft', 'content' => 'Текст'],
        'tj' => ['title' => 'Нусха', 'slug' => 'nusxa', 'content' => 'Матн'],
    ]);
    $page->tags()->sync([$first->id]);

    $this->actingAs($this->editor)
        ->put(route('admin.pages.update', $page), pageTagPayload([
            'status' => 'draft',
            'tag_ids' => [$second->id],
            'translations' => [
                'tj' => ['title' => 'Нусха', 'slug' => 'nusxa', 'content' => 'Матн'],
                'ru' => ['title' => 'Черновик', 'slug' => 'draft', 'content' => 'Текст'],
                'en' => ['title' => '', 'slug' => ''],
            ],
        ]))
        ->assertRedirect(route('admin.pages.index'));

    $page->refresh()->load('tags');

    expect($page->tags)->toHaveCount(1)
        ->and($page->tags->first()->id)->toBe($second->id);
});

it('autosaves page tags without changing status', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations(['ru' => ['name' => 'Тег', 'slug' => 'teg']]);

    $page = Page::factory()->create(['status' => ContentStatus::Draft]);
    $page->upsertTranslations([
        'ru' => ['title' => 'Черновик', 'slug' => 'draft', 'content' => 'Текст'],
    ]);

    $this->actingAs($this->editor)
        ->patchJson(route('admin.pages.autosave', $page), [
            'tag_ids' => [$tag->id],
            'translations' => [
                'ru' => [
                    'title' => 'Черновик',
                    'slug' => 'draft',
                    'content' => 'Текст',
                ],
            ],
        ])
        ->assertOk();

    $page->refresh()->load('tags');

    expect($page->status)->toBe(ContentStatus::Draft)
        ->and($page->tags)->toHaveCount(1);
});

it('exposes page tags through the headless collection API', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations([
        'ru' => ['name' => 'Контакты', 'slug' => 'kontakty'],
    ]);

    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->upsertTranslations([
        'ru' => ['title' => 'Контакты', 'slug' => 'contacts-page', 'content' => 'Текст'],
    ]);
    $page->tags()->sync([$tag->id]);
    $page->capturePublishedSnapshot();

    $this->withHeaders(pageTagBearer())
        ->getJson('/api/v1/page/contacts-page?locale=ru')
        ->assertOk()
        ->assertJsonPath('data.tags.0.name', 'Контакты')
        ->assertJsonPath('data.tags.0.slug', 'kontakty');
});
