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

function workingCopyPage(string $title = 'Опубликовано', string $slug = 'published-page'): Page
{
    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->upsertTranslations([
        'ru' => ['title' => $title, 'slug' => $slug, 'content' => '<p>Старый текст</p>'],
    ]);
    $page->capturePublishedSnapshot();

    return $page->fresh(['translations']);
}

it('keeps the public page on the published snapshot after working-copy edits', function () {
    $page = workingCopyPage();

    $page->upsertTranslations([
        'ru' => ['title' => 'Новый заголовок', 'slug' => 'published-page', 'content' => '<p>Новый текст</p>'],
    ]);
    $page->touch();

    expect($page->fresh()->hasUnpublishedChanges())->toBeTrue();

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'published-page']))
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('page.title', 'Опубликовано')
            ->where('page.content', '<p>Старый текст</p>'));
});

it('updates the public page after publishing the working copy', function () {
    $page = workingCopyPage();

    $page->upsertTranslations([
        'ru' => ['title' => 'Обновлено', 'slug' => 'published-page', 'content' => '<p>Обновлено</p>'],
    ]);

    $this->actingAs($this->publisher)
        ->post(route('admin.pages.publish-version', $page))
        ->assertRedirect(route('admin.pages.edit', $page));

    expect($page->fresh()->hasUnpublishedChanges())->toBeFalse();

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'published-page']))
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('page.title', 'Обновлено')
            ->where('page.content', '<p>Обновлено</p>'));
});

it('keeps the old public url when the slug changes in the working copy', function () {
    $page = workingCopyPage('Старая', 'old-slug');

    $page->upsertTranslations([
        'ru' => ['title' => 'Новая', 'slug' => 'new-slug', 'content' => '<p>Текст</p>'],
    ]);

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'old-slug']))
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert->where('page.title', 'Старая'));

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'new-slug']))
        ->assertNotFound();
});

it('captures a snapshot when a post is first published', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Moderation]);
    $post->upsertTranslations([
        'ru' => ['title' => 'Новость', 'slug' => 'novost', 'body' => '<p>Текст</p>'],
    ]);

    $this->actingAs($this->publisher)
        ->post(route('admin.moderation.publish', ['type' => 'post', 'id' => $post->id]))
        ->assertRedirect(route('admin.moderation.index'));

    $post->refresh();

    expect($post->status)->toBe(ContentStatus::Published)
        ->and($post->hasPublishedSnapshot())->toBeTrue();
});

it('shows the working copy banner on the page edit form', function () {
    $page = workingCopyPage();
    $page->upsertTranslations([
        'ru' => ['title' => 'Изменено', 'slug' => 'published-page', 'content' => '<p>Изменено</p>'],
    ]);

    $this->actingAs($this->publisher)
        ->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('hasUnpublishedChanges', true)
            ->where('canPublish', true));
});
