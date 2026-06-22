<?php

use App\Enums\GuideAudience;
use App\Models\Guide;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

function publishedGuide(string $slug = 'eq', array $attrs = []): Guide
{
    $guide = Guide::factory()->create($attrs);

    $guide->upsertTranslations([
        'tj' => ['title' => 'Заминҷунбӣ', 'slug' => "{$slug}-tj", 'summary' => 'Хулоса', 'content' => '<p>Матн</p>'],
        'ru' => ['title' => 'Землетрясение', 'slug' => "{$slug}-ru", 'summary' => 'Сводка', 'content' => '<p>Текст</p>'],
    ]);

    return $guide;
}

it('lists published guides for the current locale', function () {
    publishedGuide();
    Guide::factory()->draft()->create()->upsertTranslations(['ru' => ['title' => 'Черновик', 'slug' => 'draft-ru']]);

    $this->get(route('guides.index', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/guides/index')
            ->has('guides', 1)
            ->where('guides.0.title', 'Землетрясение'));
});

it('filters the catalogue by audience', function () {
    publishedGuide('general', ['audience' => GuideAudience::General]);
    publishedGuide('kids', ['audience' => GuideAudience::Children]);

    $this->get(route('guides.index', ['locale' => 'ru', 'audience' => 'children']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('guides', 1)
            ->where('guides.0.audience', 'children'));
});

it('shows a guide by its current-locale slug', function () {
    publishedGuide();

    $this->get(route('guides.show', ['locale' => 'ru', 'slug' => 'eq-ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/guides/show')
            ->where('guide.title', 'Землетрясение'));
});

it('exposes per-locale switch URLs using each locale own slug', function () {
    publishedGuide(); // eq-tj, eq-ru

    $this->get(route('guides.show', ['locale' => 'ru', 'slug' => 'eq-ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('localeSwitch.ru', fn ($url) => str_contains($url, '/ru/guides/eq-ru'))
            ->where('localeSwitch.tj', fn ($url) => str_contains($url, '/tj/guides/eq-tj')));
});

it('404s for a draft guide slug', function () {
    publishedGuide();
    Guide::factory()->draft()->create()->upsertTranslations(['ru' => ['title' => 'Ч', 'slug' => 'hidden-ru']]);

    $this->get(route('guides.show', ['locale' => 'ru', 'slug' => 'hidden-ru']))->assertNotFound();
});

it('resolves the guide when using a slug from a different locale', function () {
    publishedGuide();

    $this->get(route('guides.show', ['locale' => 'ru', 'slug' => 'eq-tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('guide.title', 'Землетрясение')
        );
});
