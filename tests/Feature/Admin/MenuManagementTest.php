<?php

use App\Enums\Role as RoleEnum;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(RoleEnum::SuperAdmin->value);

    $this->menu = Menu::create([
        'name' => 'Test Menu',
        'location' => 'test',
        'is_active' => true,
    ]);
});

test('admin can view menus index', function () {
    actingAs($this->admin)
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/menus/index'));
});

test('admin can view menu items builder', function () {
    actingAs($this->admin)
        ->get(route('admin.menus.show', $this->menu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/menus/show'));
});

test('admin can store a menu item with only the default locale', function () {
    actingAs($this->admin)
        ->post(route('admin.menus.items.store', $this->menu), [
            'url' => 'https://example.com',
            'translations' => [
                'tj' => ['title' => 'Tojiki'],
            ],
        ])
        ->assertRedirect();

    expect(MenuItem::count())->toBe(1);

    $item = MenuItem::first();
    expect($item->url)->toBe('https://example.com')
        ->and($item->translation('tj')->title)->toBe('Tojiki')
        ->and($item->translations)->toHaveCount(1);
});

test('admin can store a menu item with multiple locales', function () {
    actingAs($this->admin)
        ->post(route('admin.menus.items.store', $this->menu), [
            'url' => 'https://example.com',
            'translations' => [
                'tj' => ['title' => 'Tojiki'],
                'en' => ['title' => 'English'],
            ],
        ])
        ->assertRedirect();

    expect(MenuItem::count())->toBe(1);

    $item = MenuItem::first();
    expect($item->url)->toBe('https://example.com')
        ->and($item->translation('en')->title)->toBe('English');
});

test('admin can remove a locale translation by clearing its title', function () {
    $item = $this->menu->items()->create(['url' => 'https://example.com', 'sort_order' => 1]);
    $item->upsertTranslations([
        'tj' => ['title' => 'TJ'],
        'ru' => ['title' => 'RU'],
    ]);

    actingAs($this->admin)
        ->put(route('admin.menus.items.update', [$this->menu, $item]), [
            'url' => 'https://example.com',
            'translations' => [
                'tj' => ['title' => 'TJ'],
                'ru' => ['title' => ''],
            ],
        ])
        ->assertRedirect();

    expect($item->fresh()->translations)->toHaveCount(1)
        ->and($item->hasTranslation('ru'))->toBeFalse();
});

test('admin can reorder menu items', function () {
    $item1 = $this->menu->items()->create(['sort_order' => 1]);
    $item2 = $this->menu->items()->create(['sort_order' => 2]);

    actingAs($this->admin)
        ->post(route('admin.menus.reorder', $this->menu), [
            'items' => [
                ['id' => $item1->id, 'parent_id' => null, 'sort_order' => 2],
                ['id' => $item2->id, 'parent_id' => null, 'sort_order' => 1],
            ],
        ])
        ->assertRedirect();

    expect($item1->fresh()->sort_order)->toBe(2)
        ->and($item2->fresh()->sort_order)->toBe(1);
});
