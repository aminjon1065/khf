<?php

use App\Models\Post;
use Database\Seeders\LanguageSeeder;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('serves a valid RSS feed of published news for the locale', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations([
        'ru' => ['title' => 'Учения по гражданской обороне', 'slug' => 'drills-ru', 'excerpt' => 'Анонс', 'body' => 'Текст'],
    ]);

    $response = $this->get(route('news.rss', ['locale' => 'ru']))->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/rss+xml');

    $response->assertSee('<rss version="2.0"', false);
    $response->assertSee('<title>Учения по гражданской обороне</title>', false);
    $response->assertSee(route('news.show', ['locale' => 'ru', 'slug' => 'drills-ru']), false);
});

it('excludes drafts and other-locale posts from the feed', function () {
    Post::factory()->draft()->create()->upsertTranslations(['ru' => ['title' => 'Черновик', 'slug' => 'draft-ru']]);
    Post::factory()->create()->upsertTranslations(['en' => ['title' => 'English only', 'slug' => 'en-only']]);

    $this->get(route('news.rss', ['locale' => 'ru']))
        ->assertOk()
        ->assertDontSee('Черновик', false)
        ->assertDontSee('English only', false);
});
