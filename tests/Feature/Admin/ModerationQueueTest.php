<?php

use App\Cms\BlockSet\BlockSetRepository;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\Admin\ModerationQueueService;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

it('loads the page block set from yaml', function () {
    $blockSet = app(BlockSetRepository::class)->find('page');

    expect($blockSet->handle)->toBe('page')
        ->and($blockSet->blocks)->toHaveCount(8)
        ->and($blockSet->find('text')?->label)->toBe('Текст');
});

it('lists content awaiting moderation', function () {
    $post = Post::factory()->create([
        'author_id' => $this->editor->id,
        'status' => ContentStatus::Moderation,
    ]);
    $post->upsertTranslations(['ru' => ['title' => 'На модерации', 'slug' => 'na-moderacii']]);

    $items = app(ModerationQueueService::class)->items();

    expect($items)->toHaveCount(1)
        ->and($items->first()['content_type'])->toBe('post')
        ->and($items->first()['title'])->toBe('На модерации');
});

it('renders the moderation queue page', function () {
    $post = Post::factory()->create([
        'author_id' => $this->editor->id,
        'status' => ContentStatus::Moderation,
    ]);
    $post->upsertTranslations(['ru' => ['title' => 'Проверка', 'slug' => 'proverka']]);

    $this->actingAs($this->editor)
        ->get(route('admin.moderation.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/moderation/index')
            ->has('items', 1)
            ->where('total', 1)
            ->where('items.0.title', 'Проверка'));
});

it('passes blockset to the page edit form', function () {
    $page = Page::factory()->create();
    $page->upsertTranslations(['ru' => ['title' => 'Тест', 'slug' => 'test']]);

    $this->actingAs($this->editor)
        ->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/editorial-form')
            ->has('blockset')
            ->where('blockset.handle', 'page')
            ->has('blockset.blocks', 8));
});
