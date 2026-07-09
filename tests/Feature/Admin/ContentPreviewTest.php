<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\PreviewUrls;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([LanguageSeeder::class, RolePermissionSeeder::class]);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function previewPageWithTranslation(ContentStatus $status = ContentStatus::Draft): Page
{
    $page = Page::factory()->create(['status' => $status]);
    $page->upsertTranslations([
        'ru' => ['title' => 'Черновик', 'slug' => 'preview-page-ru', 'content' => '<p>Текст</p>'],
    ]);

    return $page;
}

function previewPostWithTranslation(ContentStatus $status = ContentStatus::Draft): Post
{
    $post = Post::factory()->create(['status' => $status]);
    $post->upsertTranslations([
        'ru' => [
            'title' => 'Новость',
            'slug' => 'preview-post-ru',
            'excerpt' => 'Кратко',
            'body' => '<p>Текст</p>',
        ],
    ]);

    return $post;
}

it('previews a draft page via signed url', function () {
    $page = previewPageWithTranslation();

    $url = app(PreviewUrls::class)->forPage($page->id)['ru'];

    $this->actingAs($this->editor)
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('public/pages/show')
            ->where('page.title', 'Черновик')
            ->where('isPreview', true));
});

it('previews a draft post via signed url', function () {
    $post = previewPostWithTranslation();

    $url = app(PreviewUrls::class)->forPost($post->id)['ru'];

    $this->actingAs($this->editor)
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('public/news/show')
            ->where('post.title', 'Новость')
            ->where('isPreview', true)
            ->where('related', []));
});

it('rejects preview urls with an invalid signature', function () {
    $page = previewPageWithTranslation();

    $this->actingAs($this->editor)
        ->get(route('admin.preview.show', ['type' => 'page', 'id' => $page->id, 'locale' => 'ru']))
        ->assertForbidden();
});

it('redirects guests away from preview urls', function () {
    $page = previewPageWithTranslation();
    $url = app(PreviewUrls::class)->forPage($page->id)['ru'];

    $this->get($url)->assertRedirect(route('login'));
});

it('still hides draft pages from the public site', function () {
    $page = previewPageWithTranslation();

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'preview-page-ru']))
        ->assertNotFound();
});

it('passes preview urls to the page edit form', function () {
    $page = previewPageWithTranslation();

    $this->actingAs($this->editor)
        ->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->has('previewUrls.ru')
            ->where('previewUrls.ru', app(PreviewUrls::class)->forPage($page->id)['ru']));
});

it('expires preview signatures after the signed window', function () {
    $page = previewPageWithTranslation();

    $url = URL::temporarySignedRoute(
        'admin.preview.show',
        now()->subMinute(),
        ['type' => 'page', 'id' => $page->id, 'locale' => 'ru'],
    );

    $this->actingAs($this->editor)
        ->get($url)
        ->assertForbidden();
});
