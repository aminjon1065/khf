<?php

use App\Enums\Role;
use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, LanguageSeeder::class]);
});

/**
 * @return array<string, string>
 */
function taxonomyApiBearer(): array
{
    $plainText = ApiToken::generate('taxonomy-test')['plainText'];

    return ['Authorization' => 'Bearer '.$plainText];
}

it('rejects taxonomy requests without a token', function () {
    $this->getJson('/api/v1/taxonomies')->assertUnauthorized();
    $this->getJson('/api/v1/taxonomies/tags')->assertUnauthorized();
});

it('lists taxonomy definitions', function () {
    $this->withHeaders(taxonomyApiBearer())
        ->getJson('/api/v1/taxonomies')
        ->assertOk()
        ->assertJsonPath('data.0.handle', 'categories')
        ->assertJsonPath('data.1.handle', 'tags')
        ->assertJsonFragment(['collections' => ['post', 'page']]);
});

it('returns taxonomy terms for tags', function () {
    $tag = Tag::factory()->create();
    $tag->upsertTranslations([
        'ru' => ['name' => 'Важное', 'slug' => 'vazhnoe'],
        'tj' => ['name' => 'Муҳим', 'slug' => 'muhim'],
    ]);

    $this->withHeaders(taxonomyApiBearer())
        ->getJson('/api/v1/taxonomies/tags?locale=ru')
        ->assertOk()
        ->assertJsonPath('handle', 'tags')
        ->assertJsonPath('terms.0.name', 'Важное')
        ->assertJsonPath('terms.0.slug', 'vazhnoe');
});

it('returns taxonomy terms for categories', function () {
    $category = Category::factory()->create(['sort_order' => 1]);
    $category->upsertTranslations([
        'ru' => ['name' => 'Новости', 'slug' => 'novosti'],
    ]);

    $this->withHeaders(taxonomyApiBearer())
        ->getJson('/api/v1/taxonomies/categories?locale=ru')
        ->assertOk()
        ->assertJsonPath('handle', 'categories')
        ->assertJsonPath('terms.0.name', 'Новости');
});

it('returns 404 for unknown taxonomies', function () {
    $this->withHeaders(taxonomyApiBearer())
        ->getJson('/api/v1/taxonomies/unknown')
        ->assertNotFound();
});

it('documents taxonomy endpoints in the meta index', function () {
    $this->getJson('/api/v1')
        ->assertOk()
        ->assertJsonFragment(['path' => '/api/v1/taxonomies'])
        ->assertJsonFragment(['path' => '/api/v1/taxonomies/{handle}']);
});

it('exposes taxonomy terms to authenticated admin users', function () {
    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Moderator->value);

    $tag = Tag::factory()->create();
    $tag->upsertTranslations([
        'ru' => ['name' => 'Админ тег', 'slug' => 'admin-tag'],
    ]);

    $this->actingAs($editor)
        ->getJson(route('admin.api.taxonomies.show', 'tags', absolute: false).'?locale=ru')
        ->assertOk()
        ->assertJsonPath('terms.0.name', 'Админ тег');
});
