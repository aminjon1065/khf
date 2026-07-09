<?php

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\Post;
use App\Support\MenuLinkCatalog;
use App\Support\MenuUrlResolver;
use Database\Seeders\LanguageSeeder;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('resolves named public routes with the active locale', function () {
    $resolver = app(MenuUrlResolver::class);

    expect($resolver->resolve(null, 'news.index', 'ru'))
        ->toBe(route('news.index', ['locale' => 'ru']));
});

it('resolves cms pages by page id route token', function () {
    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->upsertTranslations([
        'tj' => ['title' => 'TJ', 'slug' => 'tj-slug'],
        'ru' => ['title' => 'RU', 'slug' => 'ru-slug'],
    ]);

    $resolver = app(MenuUrlResolver::class);

    expect($resolver->resolve(null, 'page.'.$page->id, 'ru'))
        ->toBe(route('pages.show', ['locale' => 'ru', 'slug' => 'ru-slug']));
});

it('prefixes relative internal paths with the locale', function () {
    $resolver = app(MenuUrlResolver::class);

    expect($resolver->resolve('/news', null, 'tj'))
        ->toBe('/tj/news');
});

it('does not expose internal dashboard routes in public menu urls', function () {
    $resolver = app(MenuUrlResolver::class);

    expect($resolver->resolve(null, 'dashboard', 'ru'))->toBeNull()
        ->and($resolver->resolve(null, 'admin.dashboard', 'ru'))->toBeNull();
});

it('keeps external urls unchanged', function () {
    $resolver = app(MenuUrlResolver::class);

    expect($resolver->resolve('https://president.tj', null, 'ru'))
        ->toBe('https://president.tj');
});

it('resolves collection entries by entry route token', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations([
        'ru' => ['title' => 'Новость', 'slug' => 'novost-menu'],
    ]);

    $resolver = app(MenuUrlResolver::class);

    expect($resolver->resolve(null, 'entry.post.'.$post->id, 'ru'))
        ->toBe(route('news.show', ['locale' => 'ru', 'slug' => 'novost-menu']));
});

it('builds a nested page tree for the menu builder', function () {
    $parent = Page::factory()->create(['status' => ContentStatus::Published, 'sort_order' => 1]);
    $parent->upsertTranslations(['ru' => ['title' => 'Родитель', 'slug' => 'parent']]);
    $child = Page::factory()->create([
        'status' => ContentStatus::Published,
        'parent_id' => $parent->id,
        'sort_order' => 2,
    ]);
    $child->upsertTranslations(['ru' => ['title' => 'Дочерняя', 'slug' => 'child']]);

    $tree = MenuLinkCatalog::pageTree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['id'])->toBe($parent->id)
        ->and($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['id'])->toBe($child->id)
        ->and(collect(MenuLinkCatalog::pages())->pluck('title')->all())
        ->toContain('Родитель', '— Дочерняя');
});
