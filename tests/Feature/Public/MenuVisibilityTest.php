<?php

use App\Models\Menu;
use App\Services\Public\MenuFormatter;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(LanguageSeeder::class));

it('hides menu items that have no translation for the active locale', function () {
    $menu = Menu::create([
        'name' => 'Primary',
        'location' => 'primary',
        'is_active' => true,
    ]);

    $item = $menu->items()->create(['url' => '/about', 'sort_order' => 1]);
    $item->upsertTranslations(['tj' => ['title' => 'Дар бораи']]);

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('menus.primary', 0));

    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('menus.primary', 1)
            ->where('menus.primary.0.title', 'Дар бораи')
        );
});

it('hides child menu items without a translation while keeping visible siblings', function () {
    $menu = Menu::create([
        'name' => 'Primary',
        'location' => 'primary',
        'is_active' => true,
    ]);

    $parent = $menu->items()->create(['url' => '/section', 'sort_order' => 1]);
    $parent->upsertTranslations([
        'tj' => ['title' => 'Бахш'],
        'ru' => ['title' => 'Раздел'],
    ]);

    $childTjOnly = $menu->items()->create(['parent_id' => $parent->id, 'url' => '/tj-only', 'sort_order' => 1]);
    $childTjOnly->upsertTranslations(['tj' => ['title' => 'Танҳо TJ']]);

    $childBoth = $menu->items()->create(['parent_id' => $parent->id, 'url' => '/both', 'sort_order' => 2]);
    $childBoth->upsertTranslations([
        'tj' => ['title' => 'Ҳарду'],
        'ru' => ['title' => 'Оба'],
    ]);

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('menus.primary.0.title', 'Раздел')
            ->has('menus.primary.0.children', 1)
            ->where('menus.primary.0.children.0.title', 'Оба')
        );
});

it('formats footer menus with the same locale visibility rules', function () {
    $menu = Menu::create([
        'name' => 'Footer',
        'location' => 'footer',
        'is_active' => true,
    ]);

    $item = $menu->items()->create(['url' => '/contacts', 'sort_order' => 1]);
    $item->upsertTranslations(['ru' => ['title' => 'Контакты']]);

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('menus.footer.0.title', 'Контакты')
        );

    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('menus.footer', 0));
});

it('unit tests the menu formatter directly', function () {
    $menu = Menu::create(['name' => 'Test', 'location' => 'test', 'is_active' => true]);
    $item = $menu->items()->create(['url' => '/x', 'sort_order' => 1]);
    $item->upsertTranslations(['en' => ['title' => 'English only']]);
    $item->load('translations');

    $formatter = app(MenuFormatter::class);
    $formatted = $formatter->formatTree(collect([$item]), collect([$item]), 'en');

    expect($formatted)->toHaveCount(1)
        ->and($formatter->formatTree(collect([$item]), collect([$item]), 'ru'))->toBe([]);
});
