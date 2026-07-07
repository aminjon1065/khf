<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Document;
use App\Models\Post;
use App\Models\Tag;
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

function tagPayload(array $overrides = []): array
{
    return array_merge([
        'translations' => [
            'tj' => ['name' => 'Муҳим', 'slug' => 'muhim'],
            'ru' => ['name' => 'Важное', 'slug' => 'vazhnoe'],
            'en' => ['name' => '', 'slug' => ''],
        ],
    ], $overrides);
}

function tagPostPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'news',
        'category_id' => null,
        'status' => 'published',
        'published_at' => '2026-06-09T10:00',
        'translations' => [
            'tj' => ['title' => 'Хабар', 'slug' => 'habar', 'excerpt' => 'Анонс', 'body' => 'Матн'],
            'ru' => ['title' => 'Новость', 'slug' => 'novost', 'excerpt' => 'Анонс', 'body' => 'Текст'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

function tagDocumentPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'law',
        'source' => 'КЧС',
        'document_date' => '2026-01-15',
        'status' => 'published',
        'sort_order' => 0,
        'translations' => [
            'tj' => ['name' => 'Ҳуҷҷат', 'description' => 'Тавсиф'],
            'ru' => ['name' => 'Документ', 'description' => 'Описание'],
            'en' => ['name' => '', 'description' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.tags.index'))
        ->assertForbidden();
});

it('renders the tags list and form', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations(['ru' => ['name' => 'Тег', 'slug' => 'teg']]);

    $this->actingAs($this->editor)->get(route('admin.tags.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/tags/index')->has('tags.data', 1));

    $this->actingAs($this->editor)->get(route('admin.tags.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/tags/form')->has('locales', 3));
});

it('creates a tag with translations', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.tags.store'), tagPayload())
        ->assertRedirect(route('admin.tags.index'));

    $tag = Tag::with('translations')->first();

    expect($tag->translations)->toHaveCount(2)
        ->and($tag->translation('ru')->name)->toBe('Важное');
});

it('requires the default-locale name', function () {
    $payload = tagPayload();
    $payload['translations']['tj']['name'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.tags.create'))
        ->post(route('admin.tags.store'), $payload)
        ->assertSessionHasErrors('translations.tj.name');
});

it('enforces slug uniqueness within a locale', function () {
    $this->actingAs($this->editor)->post(route('admin.tags.store'), tagPayload());

    $second = tagPayload();
    $second['translations']['tj']['slug'] = 'muhim-2';

    $this->actingAs($this->editor)
        ->from(route('admin.tags.create'))
        ->post(route('admin.tags.store'), $second)
        ->assertSessionHasErrors('translations.ru.slug');
});

it('updates and deletes a tag', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations(['tj' => ['name' => 'Кӯҳна', 'slug' => 'kuhna']]);

    $this->actingAs($this->editor)
        ->put(route('admin.tags.update', $tag), tagPayload())
        ->assertRedirect(route('admin.tags.index'));

    expect($tag->fresh()->translation('ru')->name)->toBe('Важное');

    $this->actingAs($this->editor)
        ->delete(route('admin.tags.destroy', $tag))
        ->assertRedirect(route('admin.tags.index'));

    expect(Tag::find($tag->id))->toBeNull();
});

it('syncs tags on posts and documents', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations(['ru' => ['name' => 'Важное', 'slug' => 'vazhnoe']]);

    $post = Post::factory()->create();
    $post->upsertTranslations(['tj' => ['title' => 'Хабар', 'slug' => 'habar']]);

    $document = Document::factory()->create();
    $document->upsertTranslations(['tj' => ['name' => 'Ҳуҷҷат']]);

    $this->actingAs($this->editor)
        ->put(route('admin.posts.update', $post), array_merge(tagPostPayload(), [
            'tag_ids' => [$tag->id],
        ]))
        ->assertRedirect(route('admin.posts.index'));

    $this->actingAs($this->editor)
        ->put(route('admin.documents.update', $document), array_merge(tagDocumentPayload(), [
            'tag_ids' => [$tag->id],
        ]))
        ->assertRedirect(route('admin.documents.index'));

    expect($post->fresh()->tags)->toHaveCount(1)
        ->and($document->fresh()->tags)->toHaveCount(1);
});

it('exposes tags on the public news article page', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations(['tj' => ['name' => 'Муҳим', 'slug' => 'muhim']]);

    $post = Post::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    $post->upsertTranslations(['tj' => ['title' => 'Хабар', 'slug' => 'habar-test']]);
    $post->tags()->attach($tag);

    $this->get(route('news.show', ['locale' => 'tj', 'slug' => 'habar-test']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('post.tags', 1)
            ->where('post.tags.0', 'Муҳим')
        );
});
