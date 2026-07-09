<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed([LanguageSeeder::class, RolePermissionSeeder::class]);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

it('requires authentication for admin content search', function () {
    $this->getJson(route('admin.api.search', ['q' => 'test']))
        ->assertRedirect(route('login'));
});

it('returns matching editorial entries across collections', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations([
        'tj' => ['title' => 'Уникальный заголовок поиска', 'slug' => 'unique-search'],
    ]);

    $page = Page::factory()->create(['status' => ContentStatus::Draft]);
    $page->upsertTranslations([
        'tj' => ['title' => 'Другая страница', 'slug' => 'other-page'],
    ]);

    $this->actingAs($this->editor)
        ->getJson(route('admin.api.search', ['q' => 'Уникальный']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.type', 'post')
        ->assertJsonPath('results.0.title', 'Уникальный заголовок поиска');
});

it('ignores queries shorter than two characters', function () {
    Post::factory()->create()->upsertTranslations([
        'tj' => ['title' => 'Короткий', 'slug' => 'short'],
    ]);

    $this->actingAs($this->editor)
        ->getJson(route('admin.api.search', ['q' => 'К']))
        ->assertOk()
        ->assertJsonCount(0, 'results');
});
