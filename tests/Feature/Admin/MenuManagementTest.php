<?php

use App\Enums\ContentStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Post;
use App\Models\User;
use App\Support\MenuUrlResolver;
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

test('menus index provisions default header and footer menus when missing', function () {
    Menu::query()->delete();

    actingAs($this->admin)
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/menus/index')
            ->has('menus', 2)
            ->where('menus.0.location', 'primary')
            ->where('menus.1.location', 'footer'));

    expect(Menu::where('location', 'primary')->exists())->toBeTrue()
        ->and(Menu::where('location', 'footer')->exists())->toBeTrue();
});

test('admin can view menu items builder', function () {
    actingAs($this->admin)
        ->get(route('admin.menus.show', $this->menu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/menus/show')
            ->has('linkPageTree')
            ->has('linkCollectionEntries'));
});

test('menu builder exposes collection entries and preview urls', function () {
    $defaultLocale = Language::defaultCode();

    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations([
        $defaultLocale => ['title' => 'Новость меню', 'slug' => 'novost-menyu'],
    ]);

    $item = $this->menu->items()->create([
        'route' => 'entry.post.'.$post->id,
        'sort_order' => 1,
    ]);
    $item->upsertTranslations([$defaultLocale => ['title' => 'Ссылка на новость']]);

    actingAs($this->admin)
        ->get(route('admin.menus.show', $this->menu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('items.0.preview_url', route('news.show', ['locale' => $defaultLocale, 'slug' => 'novost-menyu']))
            ->where('linkCollectionEntries.0.handle', 'post'));
});

test('admin can store a menu item linked to a collection entry', function () {
    $post = Post::factory()->create(['status' => ContentStatus::Published]);
    $post->upsertTranslations([
        'tj' => ['title' => 'Хабар', 'slug' => 'habar'],
    ]);

    actingAs($this->admin)
        ->post(route('admin.menus.items.store', $this->menu), [
            'route' => 'entry.post.'.$post->id,
            'translations' => [
                'tj' => ['title' => 'Хабар'],
            ],
        ])
        ->assertRedirect();

    $item = MenuItem::first();

    expect($item->route)->toBe('entry.post.'.$post->id)
        ->and(app(MenuUrlResolver::class)->resolve(null, $item->route, 'tj'))
        ->toBe(route('news.show', ['locale' => 'tj', 'slug' => 'habar']));
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
