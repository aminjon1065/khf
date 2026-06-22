<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $menus = Menu::orderBy('name')->get();

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
                'is_active' => $menu->is_active,
            ],
            'items' => $items,
            'locales' => Language::active()->map(fn ($l) => ['code' => $l->code, 'native_name' => $l->native_name])->all(),
            'defaultLocale' => Language::defaultCode(),
        ]);
    }

    private function formatItem(MenuItem $item, $allItems): array
    {
        $translations = [];
        foreach ($item->translations as $t) {
            $translations[$t->locale] = ['title' => $t->title];
        }

        return [
            'id' => $item->id,
            'parent_id' => $item->parent_id,
            'url' => $item->url,
            'route' => $item->route,
            'icon' => $item->icon,
            'target' => $item->target,
            'sort_order' => $item->sort_order,
            'translations' => $translations,
            'children' => $allItems->where('parent_id', $item->id)->map(function ($child) use ($allItems) {
                return $this->formatItem($child, $allItems);
            })->values()->all(),
        ];
    }
}
