<?php

use App\Enums\ContentStatus;
use App\Models\Post;
use Database\Seeders\LanguageSeeder;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('hides a published post until its scheduled publish time', function () {
    $post = Post::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->addDay(),
    ]);
    $post->upsertTranslations(['tj' => ['title' => 'Оянда', 'slug' => 'oyanda']]);

    $this->get(route('news.show', ['locale' => 'tj', 'slug' => 'oyanda']))
        ->assertNotFound();

    $this->get(route('news.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('posts.data', 0));
});

it('hides a published post after its scheduled unpublish time', function () {
    $post = Post::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDays(2),
        'unpublished_at' => now()->subHour(),
    ]);
    $post->upsertTranslations(['tj' => ['title' => 'Гузашта', 'slug' => 'guzashta']]);

    $this->get(route('news.show', ['locale' => 'tj', 'slug' => 'guzashta']))
        ->assertNotFound();
});

it('shows a published post within its publication window', function () {
    $post = Post::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'unpublished_at' => now()->addDay(),
    ]);
    $post->upsertTranslations(['tj' => ['title' => 'Фаъол', 'slug' => 'faol']]);

    $this->get(route('news.show', ['locale' => 'tj', 'slug' => 'faol']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('post.title', 'Фаъол'));
});
