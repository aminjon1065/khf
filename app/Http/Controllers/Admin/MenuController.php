<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\DefaultMenus;
use App\Support\MenuLinkCatalog;
use App\Support\MenuUrlResolver;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        DefaultMenus::ensure();

        $menus = Menu::query()
            ->withCount('items')
            ->orderByRaw("CASE location WHEN 'primary' THEN 0 WHEN 'footer' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get()
            ->map(fn (Menu $menu): array => [
                'id' => $menu->id,
                'name' => $menu->name,
                'location' => $menu->location,
                'location_label' => DefaultMenus::locationLabel($menu->location),
                'is_active' => $menu->is_active,
                'items_count' => $menu->items_count,
            ])
            ->values()
            ->all();

        return Inertia::render('admin/menus/index', [
            'menus' => $menus,
        ]);
    }

    public function show(Menu $menu): Response
    {
        $menu->load(['items' => function ($query) {
            $query->with('translations');
        }]);

        // Build tree
        $items = $menu->items->where('parent_id', null)->map(function ($item) use ($menu) {
            return $this->formatItem($item, $menu->items);
        })->values()->all();

        return Inertia::render('admin/menus/show', [
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'location' => $menu->location,
                'location_label' => DefaultMenus::locationLabel($menu->location),
                'is_active' => $menu->is_active,
            ],
            'items' => $items,
            'locales' => Language::active()->map(fn ($l) => ['code' => $l->code, 'native_name' => $l->native_name])->all(),
            'defaultLocale' => Language::defaultCode(),
            'linkSections' => MenuLinkCatalog::sections(),
            'linkPages' => MenuLinkCatalog::pages(),
            'linkPageTree' => MenuLinkCatalog::pageTree(),
            'linkCollectionEntries' => app(MenuLinkCatalog::class)->collectionEntries(),
        ]);
    }

    private function formatItem(MenuItem $item, $allItems): array
    {
        $translations = [];
        foreach ($item->translations as $t) {
            $translations[$t->locale] = ['title' => $t->title];
        }

        $defaultLocale = Language::defaultCode();
        $previewUrl = app(MenuUrlResolver::class)->resolve($item->url, $item->route, $defaultLocale);

        return [
            'id' => $item->id,
            'parent_id' => $item->parent_id,
            'url' => $item->url,
            'route' => $item->route,
            'icon' => $item->icon,
            'target' => $item->target,
            'sort_order' => $item->sort_order,
            'preview_url' => $previewUrl,
            'translations' => $translations,
            'locales' => $item->translatedLocales(),
            'children' => $allItems->where('parent_id', $item->id)->map(function ($child) use ($allItems) {
                return $this->formatItem($child, $allItems);
            })->values()->all(),
        ];
    }
}
