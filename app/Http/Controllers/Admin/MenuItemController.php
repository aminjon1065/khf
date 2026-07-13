<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderMenuItemsRequest;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;

class MenuItemController extends Controller
{
    public function store(StoreMenuItemRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->validated();

        $parentId = $data['parent_id'] ?? null;
        $maxSort = $menu->items()->where('parent_id', $parentId)->max('sort_order') ?? 0;

        $item = $menu->items()->create([
            'parent_id' => $parentId,
            'url' => $data['url'] ?? null,
            'route' => $data['route'] ?? null,
            'target' => $data['target'] ?? '_self',
            'sort_order' => $maxSort + 1,
        ]);

        $this->syncTranslations($item, $data['translations']);

        return back()->with('toast', ['type' => 'success', 'message' => __('Menu item created.')]);
    }

    public function update(UpdateMenuItemRequest $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        $data = $request->validated();

        $parentId = $data['parent_id'] ?? null;

        if ($parentId == $item->id) {
            $parentId = null;
        }

        $item->update([
            'parent_id' => $parentId,
            'url' => $data['url'] ?? null,
            'route' => $data['route'] ?? null,
            'target' => $data['target'] ?? '_self',
        ]);

        $this->syncTranslations($item, $data['translations']);

        return back()->with('toast', ['type' => 'success', 'message' => __('Menu item updated.')]);
    }

    public function destroy(Menu $menu, MenuItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Menu item deleted.')]);
    }

    public function reorder(ReorderMenuItemsRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data['items'] as $itemData) {
            MenuItem::where('id', $itemData['id'])
                ->where('menu_id', $menu->id)
                ->update([
                    'parent_id' => $itemData['parent_id'],
                    'sort_order' => $itemData['sort_order'],
                ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => __('Menu order saved.')]);
    }

    /**
     * @param  array<string, array{title?: string|null}>  $translations
     */
    private function syncTranslations(MenuItem $item, array $translations): void
    {
        foreach ($translations as $locale => $data) {
            $title = trim((string) ($data['title'] ?? ''));

            if ($title === '') {
                $item->translations()->where('locale', $locale)->delete();

                continue;
            }

            $item->translations()->updateOrCreate(
                ['locale' => $locale],
                ['title' => $title],
            );
        }
    }
}
