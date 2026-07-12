<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

it('rejects invalid status transitions when updating a post', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations(['tj' => ['title' => 'Хабар', 'slug' => 'habar']]);

    $payload = [
        'type' => 'news',
        'category_id' => null,
        'status' => 'draft',
        'published_at' => null,
        'unpublished_at' => null,
        'translations' => [
            'tj' => ['title' => 'Хабар', 'slug' => 'habar', 'excerpt' => '', 'body' => ''],
            'ru' => ['title' => '', 'slug' => ''],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ];

    $this->actingAs($this->editor)
        ->from(route('admin.posts.edit', $post))
        ->put(route('admin.posts.update', $post), $payload)
        ->assertSessionHasErrors('status');

    expect($post->fresh()->status)->toBe(ContentStatus::Published);
});

it('allows archiving a published post', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations(['tj' => ['title' => 'Хабар', 'slug' => 'habar-archive']]);

    $payload = [
        'type' => 'news',
        'category_id' => null,
        'status' => 'archived',
        'published_at' => now()->format('Y-m-d\TH:i'),
        'unpublished_at' => null,
        'translations' => [
            'tj' => ['title' => 'Хабар', 'slug' => 'habar-archive', 'excerpt' => '', 'body' => ''],
            'ru' => ['title' => '', 'slug' => ''],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ];

    $this->actingAs($this->editor)
        ->put(route('admin.posts.update', $post), $payload)
        ->assertRedirect(route('admin.content.index', 'post'));

    expect($post->fresh()->status)->toBe(ContentStatus::Archived)
        ->and($post->fresh()->unpublished_at)->toBeNull();
});
