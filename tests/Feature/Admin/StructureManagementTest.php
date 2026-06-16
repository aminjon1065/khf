<?php

use App\Enums\Role;
use App\Models\Subdivision;
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

function subdivisionPayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'parent_id' => null,
        'sort_order' => 0,
        'email' => 'dept@example.com',
        'phone' => '+992900000000',
        'staff_count' => 25,
        'translations' => [
            'tj' => ['name' => 'Раёсат', 'head' => 'Сардор', 'functions' => 'Вазифаҳо', 'address' => 'Душанбе'],
            'ru' => ['name' => 'Управление', 'head' => 'Начальник', 'functions' => 'Функции', 'address' => 'Душанбе'],
            'en' => ['name' => '', 'head' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.structure.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.structure.index'))
        ->assertForbidden();
});

it('renders the structure list and form', function () {
    $subdivision = Subdivision::factory()->create();
    $subdivision->upsertTranslations(['tj' => ['name' => 'Тест']]);

    $this->actingAs($this->editor)->get(route('admin.structure.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/structure/index')->has('subdivisions.data', 1));

    $this->actingAs($this->editor)->get(route('admin.structure.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/structure/form')->has('locales', 3)->has('statuses', 4));
});

it('creates a subdivision under a parent', function () {
    $parent = Subdivision::factory()->create();
    $parent->upsertTranslations(['tj' => ['name' => 'Главное управление']]);

    $this->actingAs($this->editor)
        ->post(route('admin.structure.store'), subdivisionPayload(['parent_id' => $parent->id]))
        ->assertRedirect(route('admin.structure.index'));

    $child = Subdivision::with('translations')->where('parent_id', $parent->id)->first();

    expect($child)->not->toBeNull()
        ->and($child->staff_count)->toBe(25)
        ->and($child->translations)->toHaveCount(2)
        ->and($child->translation('ru')->name)->toBe('Управление');
});

it('requires the default-locale name', function () {
    $payload = subdivisionPayload();
    $payload['translations']['tj']['name'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.structure.create'))
        ->post(route('admin.structure.store'), $payload)
        ->assertSessionHasErrors('translations.tj.name');
});

it('prevents a subdivision from being its own parent', function () {
    $subdivision = Subdivision::factory()->create();
    $subdivision->upsertTranslations(['tj' => ['name' => 'Управление']]);

    $this->actingAs($this->editor)
        ->from(route('admin.structure.edit', $subdivision))
        ->put(route('admin.structure.update', $subdivision), subdivisionPayload(['parent_id' => $subdivision->id]))
        ->assertSessionHasErrors('parent_id');
});

it('updates and deletes a subdivision', function () {
    $subdivision = Subdivision::factory()->create();
    $subdivision->upsertTranslations(['tj' => ['name' => 'Старое']]);

    $this->actingAs($this->editor)
        ->put(route('admin.structure.update', $subdivision), subdivisionPayload(['sort_order' => 3]))
        ->assertRedirect(route('admin.structure.index'));

    expect($subdivision->fresh()->sort_order)->toBe(3);

    $this->actingAs($this->editor)
        ->delete(route('admin.structure.destroy', $subdivision))
        ->assertRedirect(route('admin.structure.index'));

    expect(Subdivision::find($subdivision->id))->toBeNull();
});
