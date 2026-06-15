<?php

use App\Models\Alert;
use App\Models\ApiToken;
use App\Models\Incident;
use App\Models\Post;
use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

/**
 * @return array<string, string>
 */
function apiBearer(): array
{
    $plainText = ApiToken::generate('test-integrator')['plainText'];

    return ['Authorization' => 'Bearer '.$plainText];
}

it('exposes an open discovery endpoint', function () {
    $this->getJson('/api/v1')
        ->assertOk()
        ->assertJsonStructure(['name', 'version', 'documentation', 'endpoints']);
});

it('rejects data requests without a token', function () {
    $this->getJson('/api/v1/alerts')->assertUnauthorized();
    $this->getJson('/api/v1/incidents')->assertUnauthorized();
    $this->getJson('/api/v1/news')->assertUnauthorized();
});

it('rejects an invalid token', function () {
    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson('/api/v1/alerts')
        ->assertUnauthorized();
});

it('rejects an expired token', function () {
    $plainText = ApiToken::generate('expired', now()->subDay())['plainText'];

    $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->getJson('/api/v1/alerts')
        ->assertUnauthorized();
});

it('returns only active alerts to an authenticated client', function () {
    $alert = Alert::factory()->create();
    $alert->upsertTranslations(['tj' => ['title' => 'Сел', 'body' => 'Опасность схода селя']]);
    Alert::factory()->draft()->create();

    $this->withHeaders(apiBearer())
        ->getJson('/api/v1/alerts?locale=tj')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Сел')
        ->assertJsonPath('data.0.hazard_level.value', $alert->hazard_level->value);
});

it('paginates only active incidents', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations(['tj' => ['title' => 'Заминҷунбӣ', 'description' => 'd']]);
    Incident::factory()->resolved()->create();

    $this->withHeaders(apiBearer())
        ->getJson('/api/v1/incidents?locale=tj')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Заминҷунбӣ')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('returns published news and excludes drafts', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations(['tj' => ['title' => 'Хабар', 'slug' => 'habar', 'excerpt' => 'Анонс', 'body' => 'Матн']]);
    $draft = Post::factory()->draft()->create();
    $draft->upsertTranslations(['tj' => ['title' => 'Лоиҳа', 'slug' => 'loiha']]);

    $this->withHeaders(apiBearer())
        ->getJson('/api/v1/news?locale=tj')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Хабар')
        ->assertJsonMissingPath('data.0.body');
});

it('includes the full body only on the news show endpoint', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations(['tj' => ['title' => 'Н', 'slug' => 'n', 'excerpt' => 'A', 'body' => 'Полный текст']]);

    $this->withHeaders(apiBearer())
        ->getJson("/api/v1/news/{$post->id}?locale=tj")
        ->assertOk()
        ->assertJsonPath('data.body', 'Полный текст')
        ->assertJsonPath('data.slug', 'n');
});

it('returns 404 for an unpublished post on show', function () {
    $draft = Post::factory()->draft()->create();
    $draft->upsertTranslations(['tj' => ['title' => 'Лоиҳа', 'slug' => 'loiha']]);

    $this->withHeaders(apiBearer())
        ->getJson("/api/v1/news/{$draft->id}")
        ->assertNotFound();
});

it('serves content in the requested locale', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations([
        'tj' => ['title' => 'Сарлавҳа', 'slug' => 'sarlavha'],
        'ru' => ['title' => 'Заголовок', 'slug' => 'zagolovok'],
    ]);

    $this->withHeaders(apiBearer())
        ->getJson('/api/v1/news?locale=ru')
        ->assertJsonPath('data.0.title', 'Заголовок');

    $this->withHeaders(apiBearer())
        ->getJson('/api/v1/news?locale=tj')
        ->assertJsonPath('data.0.title', 'Сарлавҳа');
});

it('applies rate-limit headers', function () {
    $this->withHeaders(apiBearer())
        ->getJson('/api/v1/alerts')
        ->assertOk()
        ->assertHeader('X-RateLimit-Limit', 60);
});

it('mints a working token via the api:token command', function () {
    $this->artisan('api:token', ['name' => 'Integrator'])->assertSuccessful();

    expect(ApiToken::where('name', 'Integrator')->exists())->toBeTrue();
});
