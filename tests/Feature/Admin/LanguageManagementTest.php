<?php

use App\Enums\Role;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
});

it('redirects guests to login', function () {
    $this->get(route('admin.languages.index'))->assertRedirect(route('login'));
});

it('forbids a moderator from managing languages', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->get(route('admin.languages.index'))
        ->assertForbidden();
});

it('lists languages for a super admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.languages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/languages/index')
            ->has('languages.data', 3)
            ->has('filters.search')
        );
});

it('filters languages by search', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.languages.index', ['search' => 'Русский']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('languages.data', 1)
            ->where('languages.data.0.code', 'ru')
        );
});

it('creates a language', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.languages.store'), [
            'code' => 'uz',
            'name' => 'Uzbek',
            'native_name' => 'Oʻzbek',
            'hreflang' => 'uz',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 4,
        ])
        ->assertRedirect(route('admin.languages.index'));

    expect(Language::where('code', 'uz')->exists())->toBeTrue();
});

it('validates a unique code on create', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.languages.index'))
        ->post(route('admin.languages.store'), [
            'code' => 'tj',
            'name' => 'Duplicate',
            'native_name' => 'Duplicate',
            'hreflang' => 'tg',
            'direction' => 'ltr',
        ])
        ->assertSessionHasErrors('code');
});

it('keeps a single default language on update', function () {
    $english = Language::where('code', 'en')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('admin.languages.update', $english), [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'hreflang' => 'en',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 3,
        ])
        ->assertRedirect(route('admin.languages.index'));

    expect(Language::where('is_default', true)->pluck('code')->all())->toBe(['en']);
});

it('deletes a non-default language', function () {
    $english = Language::where('code', 'en')->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.languages.destroy', $english))
        ->assertRedirect(route('admin.languages.index'));

    expect(Language::where('code', 'en')->exists())->toBeFalse();
});

it('refuses to delete the default language', function () {
    $default = Language::where('code', 'tj')->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.languages.destroy', $default))
        ->assertRedirect(route('admin.languages.index'));

    expect(Language::where('code', 'tj')->exists())->toBeTrue();
});
