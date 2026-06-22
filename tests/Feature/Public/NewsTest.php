<?php

use App\Models\Post;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

function publishedPost(array $locales = ['tj', 'ru'], string $slugPrefix = 'post'): Post
{
    $post = Post::factory()->create();

    foreach ($locales as $locale) {
        $post->upsertTranslations([
            $locale => ['title' => "Заголовок {$locale}", 'slug' => "{$slugPrefix}-{$locale}", 'excerpt' => 'Анонс', 'body' => 'Текст'],
        ]);
    }

    return $post;
}

it('shows the latest published posts on the homepage', function () {
    publishedPost(['tj', 'ru']);
    Post::factory()->draft()->create()->upsertTranslations(['tj' => ['title' => 'Черновик', 'slug' => 'home-draft']]);

    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->has('latestPosts', 1)
        );
});

it('lists only published posts that have a translation in the current locale', function () {
    publishedPost(['tj', 'ru']);
    Post::factory()->draft()->create()->upsertTranslations(['tj' => ['title' => 'Черновик', 'slug' => 'draft-tj']]);
    publishedPost(['ru'], 'ru-only'); // no tj translation

    $this->get(route('news.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/news/index')
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Заголовок tj')
        );
});

it('shows a published article by its localized slug', function () {
    publishedPost(['tj']);

    $this->get(route('news.show', ['locale' => 'tj', 'slug' => 'post-tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/news/show')
            ->has('post.title')
            ->has('post.body')
            ->has('related')
        );
});

it('returns 404 for a draft post slug', function () {
    Post::factory()->draft()->create()->upsertTranslations(['tj' => ['title' => 'Черновик', 'slug' => 'secret-draft']]);

    $this->get(route('news.show', ['locale' => 'tj', 'slug' => 'secret-draft']))->assertNotFound();
});

it('returns 404 for an unknown slug', function () {
    $this->get(route('news.show', ['locale' => 'tj', 'slug' => 'does-not-exist']))->assertNotFound();
});
