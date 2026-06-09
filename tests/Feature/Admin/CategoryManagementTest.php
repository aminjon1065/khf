<?php

use App\Enums\Role;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function categoryPayload(array $overrides = []): array
{
    return array_merge([
        'sort_order' => 0,
        'translations' => [
            'tj' => ['name' => 'Хабарҳо', 'slug' => 'habarho'],
            'ru' => ['name' => 'Новости', 'slug' => 'novosti'],
            'en' => ['name' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});

it('renders the categories list and form', function () {
    $category = Category::factory()->create();
    $category->upsertTranslations(['tj' => ['name' => 'Т', 'slug' => 't-cat']]);

    $this->actingAs($this->editor)->get(route('admin.categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/categories/index')->has('categories.data', 1));

    $this->actingAs($this->editor)->get(route('admin.categories.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/categories/form')->has('locales', 3));
});

it('creates a category with translations', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.categories.store'), categoryPayload())
        ->assertRedirect(route('admin.categories.index'));

    $category = Category::with('translations')->first();

    expect($category->translations)->toHaveCount(2)
        ->and($category->translation('ru')->name)->toBe('Новости');
});

it('requires the default-locale name', function () {
    $payload = categoryPayload();
    $payload['translations']['tj']['name'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.categories.create'))
        ->post(route('admin.categories.store'), $payload)
        ->assertSessionHasErrors('translations.tj.name');
});

it('enforces slug uniqueness within a locale', function () {
    $this->actingAs($this->editor)->post(route('admin.categories.store'), categoryPayload());

    $second = categoryPayload();
    $second['translations']['tj']['slug'] = 'habarho-2';

    $this->actingAs($this->editor)
        ->from(route('admin.categories.create'))
        ->post(route('admin.categories.store'), $second)
        ->assertSessionHasErrors('translations.ru.slug');
});

it('updates and deletes a category', function () {
    $category = Category::factory()->create();
    $category->upsertTranslations(['tj' => ['name' => 'Старое', 'slug' => 'staroe']]);

    $this->actingAs($this->editor)
        ->put(route('admin.categories.update', $category), categoryPayload(['sort_order' => 5]))
        ->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->sort_order)->toBe(5);

    $this->actingAs($this->editor)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::find($category->id))->toBeNull();
});
