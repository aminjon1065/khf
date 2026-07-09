<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, LanguageSeeder::class]);

    $this->publisher = User::factory()->withTwoFactor()->create();
    $this->publisher->assignRole(Role::Publisher->value);
});

it('autosaves page translations without changing status', function () {
    $page = Page::factory()->create(['status' => ContentStatus::Draft]);
    $page->upsertTranslations([
        'ru' => ['title' => 'Старый заголовок', 'slug' => 'old-slug', 'content' => '<p>Старый</p>'],
    ]);

    $this->actingAs($this->publisher)
        ->patchJson(route('admin.pages.autosave', $page), [
            'translations' => [
                'ru' => [
                    'title' => 'Новый заголовок',
                    'slug' => 'old-slug',
                    'content' => '<p>Новый</p>',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonStructure(['saved_at']);

    $page->refresh();

    expect($page->status)->toBe(ContentStatus::Draft)
        ->and($page->translation('ru')?->title)->toBe('Новый заголовок');
});

it('autosaves post fields without changing status or creating a revision', function () {
    $post = Post::factory()->create([
        'status' => ContentStatus::Moderation,
        'published_at' => null,
    ]);
    $post->upsertTranslations([
        'ru' => ['title' => 'Черновик', 'slug' => 'draft', 'body' => '<p>Текст</p>'],
    ]);

    $revisionCount = $post->revisions()->count();

    $this->actingAs($this->publisher)
        ->patchJson(route('admin.posts.autosave', $post), [
            'type' => 'news',
            'category_id' => null,
            'tag_ids' => [],
            'published_at' => '',
            'unpublished_at' => '',
            'translations' => [
                'ru' => [
                    'title' => 'Обновлённый заголовок',
                    'slug' => 'draft',
                    'excerpt' => 'Анонс',
                    'body' => '<p>Обновлённый текст</p>',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonStructure(['saved_at']);

    $post->refresh();

    expect($post->status)->toBe(ContentStatus::Moderation)
        ->and($post->translation('ru')?->title)->toBe('Обновлённый заголовок')
        ->and($post->revisions()->count())->toBe($revisionCount);
});

it('shows editorial overview on the dashboard for publishers', function () {
    Post::factory()->create([
        'status' => ContentStatus::Moderation,
        'published_at' => now(),
    ]);
    Post::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    $this->actingAs($this->publisher)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('editorial.recent_updates')
            ->has('editorial.scheduled_posts', 1)
            ->where('editorial.moderation_queue', 1)
        );
});
