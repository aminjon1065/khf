<?php

use App\Enums\ContentStatus;
use App\Models\ApiToken;
use App\Models\Page;
use App\Models\Post;
use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

/**
 * @return array<string, string>
 */
function headlessApiBearer(): array
{
    $plainText = ApiToken::generate('headless-test')['plainText'];

    return ['Authorization' => 'Bearer '.$plainText];
}

it('rejects headless collection requests without a token', function () {
    $this->getJson('/api/v1/page')->assertUnauthorized();
    $this->getJson('/api/v1/globals/president')->assertUnauthorized();
});

it('lists published pages from the headless collection endpoint', function () {
    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->translations()->create([
        'locale' => 'tj',
        'title' => 'О нас',
        'slug' => 'about-us',
        'content' => 'Текст страницы',
    ]);

    $draft = Page::factory()->create(['status' => ContentStatus::Draft]);
    $draft->translations()->create([
        'locale' => 'tj',
        'title' => 'Черновик',
        'slug' => 'draft-page',
        'content' => 'Скрыто',
    ]);

    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/page?locale=tj')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.collection', 'page')
        ->assertJsonPath('data.0.title', 'О нас')
        ->assertJsonPath('data.0.slug', 'about-us')
        ->assertJsonMissingPath('data.0.fields');
});

it('shows a page by slug with full translation fields', function () {
    $page = Page::factory()->create(['status' => ContentStatus::Published]);
    $page->translations()->create([
        'locale' => 'tj',
        'title' => 'Контакты',
        'slug' => 'contacts-page',
        'content' => '<p>Адрес</p>',
    ]);

    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/page/contacts-page?locale=tj')
        ->assertOk()
        ->assertJsonPath('data.title', 'Контакты')
        ->assertJsonPath('data.fields.content', '<p>Адрес</p>')
        ->assertJsonPath('data.collection', 'page');
});

it('lists and shows posts via the post collection and news alias', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published, 'published_at' => now()]);
    $post->translations()->create([
        'locale' => 'tj',
        'title' => 'Хабар',
        'slug' => 'habar-api',
        'excerpt' => 'Анонс',
        'body' => 'Полный текст',
    ]);

    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/post?locale=tj')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Хабар');

    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/post/habar-api?locale=tj')
        ->assertOk()
        ->assertJsonPath('data.fields.body', 'Полный текст');

    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/news?locale=tj')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Хабар');
});

it('returns a cms global by handle', function () {
    config([
        'cms.globals.president.fallback' => [
            'url' => 'https://president.tj',
            'photo' => '/images/president.webp',
        ],
    ]);

    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/globals/president?locale=tj')
        ->assertOk()
        ->assertJsonPath('handle', 'president')
        ->assertJsonPath('data.url', 'https://president.tj');
});

it('returns 404 for unknown collections and slugs', function () {
    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/not-a-collection')
        ->assertNotFound();

    $this->withHeaders(headlessApiBearer())
        ->getJson('/api/v1/page/missing-slug')
        ->assertNotFound();
});

it('documents headless endpoints on the discovery route', function () {
    $this->getJson('/api/v1')
        ->assertOk()
        ->assertJsonFragment(['path' => '/api/v1/globals/{handle}'])
        ->assertJsonFragment(['path' => '/api/v1/page/{slug}']);
});
