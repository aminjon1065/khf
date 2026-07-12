<?php

use App\Models\Category;
use App\Models\Post;
use App\Services\Public\PostShowPresenter;
use Database\Seeders\LanguageSeeder;
use Illuminate\Database\Eloquent\Model;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('defines card and show eager-load contracts', function () {
    expect(PostShowPresenter::CARD_WITH)->toContain('category.translations')
        ->and(PostShowPresenter::CARD_WITH)->toContain('translations')
        ->and(PostShowPresenter::CARD_WITH)->toContain('media')
        ->and(PostShowPresenter::SHOW_WITH)->toContain('category.translations')
        ->and(PostShowPresenter::SHOW_WITH)->toContain('tags.translations')
        ->and(PostShowPresenter::SHOW_WITH)->toContain('author');
});

it('card loadMissing category without triggering lazy loading violations', function () {
    Model::preventLazyLoading();

    $category = Category::factory()->create();
    $category->upsertTranslations([
        'tj' => ['name' => 'Гражданская защита', 'slug' => 'civil-defense'],
    ]);

    $post = Post::factory()->create(['category_id' => $category->id]);
    $post->upsertTranslations([
        'tj' => [
            'title' => 'Заголовок',
            'slug' => 'headline-tj',
            'excerpt' => 'Анонс',
            'body' => 'Текст',
        ],
    ]);

    $fresh = Post::query()->findOrFail($post->id);

    $card = app(PostShowPresenter::class)->card($fresh, 'tj');

    expect($card['category'])->toBe('Гражданская защита')
        ->and($card['slug'])->toBe('headline-tj');
});

it('present loadMissing show relations for an unloaded post', function () {
    Model::preventLazyLoading();

    $category = Category::factory()->create();
    $category->upsertTranslations([
        'tj' => ['name' => 'Профилактика', 'slug' => 'prevention'],
    ]);

    $post = Post::factory()->create(['category_id' => $category->id]);
    $post->upsertTranslations([
        'tj' => [
            'title' => 'Статья',
            'slug' => 'article-tj',
            'excerpt' => 'Анонс',
            'body' => 'Полный текст',
        ],
    ]);

    $fresh = Post::query()->findOrFail($post->id);

    $payload = app(PostShowPresenter::class)->present($fresh, 'tj');

    expect($payload['post']['category'])->toBe('Профилактика')
        ->and($payload['post']['title'])->toBe('Статья')
        ->and($payload['post']['tags'])->toBeArray();
});
