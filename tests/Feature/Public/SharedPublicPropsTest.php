<?php

use App\Enums\HazardLevel;
use App\Models\Alert;
use App\Models\Menu;
use App\Services\Public\SharedPublicProps;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    Cache::flush();
});

it('caches menus per locale and bumps the version when a menu item changes', function () {
    $menu = Menu::create([
        'name' => 'Primary',
        'location' => 'primary',
        'is_active' => true,
    ]);

    $item = $menu->items()->create(['url' => '/about', 'sort_order' => 1]);
    $item->upsertTranslations(['tj' => ['title' => 'Дар бораи']]);

    $props = app(SharedPublicProps::class);
    $versionBefore = $props->version(SharedPublicProps::GROUP_MENUS);

    $first = $props->menus('tj');
    expect($first['primary'][0]['title'] ?? null)->toBe('Дар бораи')
        ->and($props->version(SharedPublicProps::GROUP_MENUS))->toBe($versionBefore);

    $item->upsertTranslations(['tj' => ['title' => 'Обновлено']]);

    expect($props->version(SharedPublicProps::GROUP_MENUS))->toBeGreaterThan($versionBefore)
        ->and($props->menus('tj')['primary'][0]['title'] ?? null)->toBe('Обновлено');
});

it('caches active alerts and refreshes after an alert is saved', function () {
    $alert = Alert::factory()->critical()->create();
    $alert->upsertTranslations(['tj' => ['title' => 'Критично', 'body' => 'Текст']]);

    $props = app(SharedPublicProps::class);
    $versionBefore = $props->version(SharedPublicProps::GROUP_ALERTS);

    expect($props->activeAlerts('tj'))->toHaveCount(1)
        ->and($props->activeAlerts('tj')[0]['title'])->toBe('Критично')
        ->and($props->version(SharedPublicProps::GROUP_ALERTS))->toBe($versionBefore);

    $alert->update(['hazard_level' => HazardLevel::Danger]);

    expect($props->version(SharedPublicProps::GROUP_ALERTS))->toBeGreaterThan($versionBefore)
        ->and($props->activeAlerts('tj')[0]['level'])->toBe(HazardLevel::Danger->value);
});

it('serves shared menus and alerts through Inertia without lazy-load failures', function () {
    $menu = Menu::create([
        'name' => 'Primary',
        'location' => 'primary',
        'is_active' => true,
    ]);
    $item = $menu->items()->create(['url' => '/news', 'sort_order' => 1]);
    $item->upsertTranslations(['tj' => ['title' => 'Ахбор']]);

    $alert = Alert::factory()->create(['hazard_level' => HazardLevel::Danger]);
    $alert->upsertTranslations(['tj' => ['title' => 'Опасно']]);

    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menus.primary.0.title', 'Ахбор')
            ->has('activeAlerts', 1)
            ->where('activeAlerts.0.title', 'Опасно')
        );
});
