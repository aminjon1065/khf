<?php

use App\Models\Post;
use App\Models\Page;

use App\Enums\ContentStatus;

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
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('public/search')
            ->has('results')
            ->where('query', 'test')
        );
});
