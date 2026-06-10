<?php

use App\Enums\ContentStatus;
use App\Models\Page;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

function publishedPage(string $slugPrefix = 'about', ContentStatus $status = ContentStatus::Published): Page
{
    $page = Page::factory()->create(['status' => $status]);

    $page->upsertTranslations([
        'tj' => ['title' => 'Дар бораи', 'slug' => "{$slugPrefix}-tj", 'content' => '<p>Матн</p>', 'seo_title' => 'СЕО', 'seo_description' => 'Тавсиф'],
        'ru' => ['title' => 'О Комитете', 'slug' => "{$slugPrefix}-ru", 'content' => '<p>Текст</p>', 'seo_title' => 'СЕО', 'seo_description' => 'Описание'],
    ]);

    return $page;
}

it('renders a published page by its current-locale slug with SEO', function () {
    publishedPage();

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'about-ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/pages/show')
            ->where('page.title', 'О Комитете')
            ->where('seo.description', 'Описание'));
});

it('404s for an unpublished page', function () {
    publishedPage('draft', ContentStatus::Draft);

    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'draft-ru']))->assertNotFound();
});

it('404s when the slug does not exist in the current locale', function () {
    publishedPage();

    // tj slug requested under ru locale → no match
    $this->get(route('pages.show', ['locale' => 'ru', 'slug' => 'about-tj']))->assertNotFound();
});

it('shares published top-level pages of the current locale for the footer nav', function () {
    publishedPage();

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navPages', fn ($pages) => collect($pages)->contains(
                fn ($p) => $p['slug'] === 'about-ru' && $p['title'] === 'О Комитете',
            )));
});
