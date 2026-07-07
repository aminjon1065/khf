<?php

use App\Enums\ContentStatus;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Leader;
use App\Models\Page;
use App\Models\Post;
use App\Models\Statistic;
use App\Models\Subdivision;
use App\Models\Tender;
use App\Models\Vacancy;
use Inertia\Testing\AssertableInertia;

it('returns search results from posts and pages', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'Important Earthquake News',
        'slug' => 'earthquake-news',
        'body' => 'Details about the earthquake...',
    ]);

    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->translations()->create([
        'locale' => 'tj',
        'title' => 'Earthquake Safety Guide',
        'slug' => 'safety-guide',
        'content' => 'How to stay safe...',
    ]);

    // Add noise in another language
    $page->translations()->create([
        'locale' => 'ru',
        'title' => 'Землетрясение',
        'slug' => 'zemletryasenie',
        'content' => 'Как оставаться в безопасности',
    ]);

    // Hit the API
    $response = $this->getJson('/tj/search/api?q=Earthquake');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'title' => 'Important Earthquake News',
        ])
        ->assertJsonFragment([
            'title' => 'Earthquake Safety Guide',
        ]);
});

it('filters out results that do not match the current locale', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'Tornado Warning',
        'slug' => 'tornado-tj',
        'body' => 'Warning...',
    ]);

    $post->translations()->create([
        'locale' => 'ru',
        'title' => 'Russian Tornado Warning',
        'slug' => 'tornado-ru',
        'body' => 'Внимание...',
    ]);

    // Search in TJ locale using a word from RU (should not find the RU version because we filter by locale)
    $response = $this->getJson('/tj/search/api?q=Russian');
    $response->assertOk()->assertJsonCount(0, 'data');

    // Search in RU locale using the RU word
    $response2 = $this->getJson('/ru/search/api?q=Russian');
    $response2->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Russian Tornado Warning']);
});

it('returns short results if query is less than 2 characters', function () {
    $response = $this->getJson('/tj/search/api?q=A');
    $response->assertOk()->assertJsonCount(0, 'data');
});

it('renders the search page with inertia', function () {
    $response = $this->get('/tj/search?q=test');

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/search')
            ->has('results')
            ->has('pagination')
            ->has('contentTypes', 11)
            ->where('query', 'test')
        );
});

it('filters search results by content type', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'ru',
        'title' => 'Новость о пожаре',
        'slug' => 'fire-news',
        'body' => 'Текст',
    ]);

    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->translations()->create([
        'locale' => 'ru',
        'title' => 'Страница о пожаре',
        'slug' => 'fire-page',
        'content' => 'Текст',
    ]);

    $this->get('/ru/search?q=пожар&type=post')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $inertia) => $inertia
            ->has('results', 1)
            ->where('results.0.type', 'post')
            ->where('filters.type', 'post')
        );
});

it('finds cyrillic queries in the current locale', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'ru',
        'title' => 'Землетрясение в Гиссаре',
        'slug' => 'zemletryasenie-gissar',
        'body' => 'Оперативная сводка',
    ]);

    $this->getJson('/ru/search/api?q=Землетрясение')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Землетрясение в Гиссаре']);
});

it('highlights matches in api search results', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'Заминларза',
        'slug' => 'zaminlarza',
        'body' => 'Маълумот',
    ]);

    $this->getJson('/tj/search/api?q=Замин')
        ->assertOk()
        ->assertJsonPath('data.0.highlighted_title', fn ($value) => str_contains($value, '<mark'));
});

it('paginates search results on the full page', function () {
    for ($i = 1; $i <= 25; $i++) {
        $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()->subDays($i)]);
        $post->translations()->create([
            'locale' => 'en',
            'title' => "Emergency report {$i}",
            'slug' => "emergency-report-{$i}",
            'body' => 'Details',
        ]);
    }

    $this->get('/en/search?q=Emergency&page=2')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $inertia) => $inertia
            ->where('pagination.current_page', 2)
            ->where('pagination.per_page', 20)
            ->where('pagination.total', 25)
            ->has('results', 5)
        );
});

it('returns published vacancies in search results', function () {
    $vacancy = Vacancy::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $vacancy->translations()->create([
        'locale' => 'tj',
        'title' => 'Rescue Inspector',
        'slug' => 'rescue-inspector',
        'summary' => 'Join the emergency service',
        'description' => 'Full job description...',
    ]);

    $this->getJson('/tj/search/api?q=Inspector')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Rescue Inspector', 'type' => 'vacancy']);
});

it('returns published tenders in search results', function () {
    $tender = Tender::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $tender->translations()->create([
        'locale' => 'tj',
        'title' => 'Excavator Procurement',
        'slug' => 'excavator-procurement',
        'summary' => 'Purchase of heavy machinery',
        'description' => 'Full tender description...',
    ]);

    $this->getJson('/tj/search/api?q=Excavator')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Excavator Procurement', 'type' => 'tender']);
});

it('returns published leaders in search results', function () {
    $leader = Leader::factory()->create(['status' => ContentStatus::Published]);
    $leader->translations()->create([
        'locale' => 'tj',
        'full_name' => 'Karim Rescuer',
        'position' => 'Chief Inspector',
    ]);

    $this->getJson('/tj/search/api?q=Karim')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Karim Rescuer', 'type' => 'leader']);
});

it('returns published subdivisions in search results', function () {
    $subdivision = Subdivision::factory()->create(['status' => ContentStatus::Published]);
    $subdivision->translations()->create([
        'locale' => 'tj',
        'name' => 'Rescue Operations Department',
        'functions' => 'Coordinates rescue efforts',
    ]);

    $this->getJson('/tj/search/api?q=Rescue')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Rescue Operations Department', 'type' => 'subdivision']);
});

it('returns published galleries in search results', function () {
    $gallery = Gallery::factory()->create(['status' => ContentStatus::Published]);
    $gallery->translations()->create([
        'locale' => 'tj',
        'title' => 'Earthquake Drill Photos',
        'slug' => 'earthquake-drill-photos',
        'description' => 'Photos from the drill',
    ]);

    $this->getJson('/tj/search/api?q=Drill')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Earthquake Drill Photos', 'type' => 'gallery']);
});

it('returns published faqs in search results', function () {
    $faq = Faq::factory()->create(['status' => ContentStatus::Published]);
    $faq->translations()->create([
        'locale' => 'tj',
        'question' => 'How to report a wildfire?',
        'answer' => 'Call the hotline immediately.',
    ]);

    $this->getJson('/tj/search/api?q=wildfire')
        ->assertOk()
        ->assertJsonFragment(['title' => 'How to report a wildfire?', 'type' => 'faq']);
});

it('returns published statistics in search results', function () {
    $statistic = Statistic::factory()->create(['status' => ContentStatus::Published]);
    $statistic->translations()->create([
        'locale' => 'tj',
        'label' => 'Rescue Operations Conducted',
        'unit' => 'units',
    ]);

    $this->getJson('/tj/search/api?q=Operations')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Rescue Operations Conducted', 'type' => 'statistic']);
});
