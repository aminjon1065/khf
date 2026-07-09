<?php

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Services\Cms\PublishedContentCache;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    Cache::flush();
    config(['cms.content_cache.enabled' => true]);
});

it('reuses cached values until the collection version is bumped', function () {
    $cache = app(PublishedContentCache::class);
    $calls = 0;

    $cache->remember('page', 'tj', 'probe', function () use (&$calls) {
        $calls++;

        return 'first';
    });

    $cache->remember('page', 'tj', 'probe', function () use (&$calls) {
        $calls++;

        return 'second';
    });

    expect($calls)->toBe(1);

    $cache->bump('page');

    $result = $cache->remember('page', 'tj', 'probe', function () use (&$calls) {
        $calls++;

        return 'third';
    });

    expect($calls)->toBe(2)
        ->and($result)->toBe('third');
});

it('bumps the page collection when a page is saved', function () {
    $cache = app(PublishedContentCache::class);
    $versionBefore = $cache->version('page');

    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->translations()->create([
        'locale' => 'tj',
        'title' => 'Test page',
        'slug' => 'test-page',
        'body' => 'Body',
    ]);

    expect($cache->version('page'))->toBeGreaterThan($versionBefore);
});

it('bumps the post collection when a category is saved', function () {
    $cache = app(PublishedContentCache::class);
    $versionBefore = $cache->version('post');

    $category = Category::factory()->create();
    $category->translations()->create([
        'locale' => 'tj',
        'name' => 'News',
        'slug' => 'news',
    ]);

    expect($cache->version('post'))->toBeGreaterThan($versionBefore);
});

it('builds a slug index for published pages and posts', function () {
    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->translations()->create([
        'locale' => 'tj',
        'title' => 'About',
        'slug' => 'about-us',
        'body' => 'Body',
    ]);

    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'News item',
        'slug' => 'news-item',
        'body' => 'Body',
        'excerpt' => 'Excerpt',
    ]);

    $cache = app(PublishedContentCache::class);

    expect($cache->resolveSlugId('page', 'about-us'))->toBe($page->id)
        ->and($cache->resolveSlugId('post', 'news-item'))->toBe($post->id);
});

it('warms slug indexes globals and locale fragments', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'Warm me',
        'slug' => 'warm-me',
        'body' => 'Body',
        'excerpt' => 'Excerpt',
    ]);

    Artisan::call('cms:cache-warm', ['--locale' => 'tj']);

    $cache = app(PublishedContentCache::class);

    expect($cache->resolveSlugId('post', 'warm-me'))->toBe($post->id)
        ->and(Cache::has($cache->key('post', 'tj', 'home.latest')))->toBeTrue();
});

it('serves a cached public post after warm', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'Cached headline',
        'slug' => 'cached-headline',
        'body' => 'Body',
        'excerpt' => 'Excerpt',
    ]);

    Artisan::call('cms:cache-warm', ['--locale' => 'tj']);

    $this->get('/tj/news/cached-headline')
        ->assertOk()
        ->assertSee('Cached headline', false);
});

it('rejects an unknown locale for cache warm', function () {
    expect(Artisan::call('cms:cache-warm', ['--locale' => 'zz']))->toBe(1);
});
