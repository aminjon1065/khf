<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function store(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'url' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'in:_self,_blank'],
            'translations' => ['required', 'array'],
            'translations.*.title' => ['required', 'string', 'max:255'],
        ]);

        $parentId = $data['parent_id'] ?? null;
        $maxSort = $menu->items()->where('parent_id', $parentId)->max('sort_order') ?? 0;

        $item = $menu->items()->create([
            'parent_id' => $parentId,
            'url' => $data['url'] ?? null,
            'route' => $data['route'] ?? null,
            'target' => $data['target'] ?? '_self',
            'sort_order' => $maxSort + 1,
        ]);

        $item->upsertTranslations($data['translations']);

        return back()->with('toast', ['type' => 'success', 'message' => __('Menu item created.')]);
    }

    public function update(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'url' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'in:_self,_blank'],
            'translations' => ['required', 'array'],
            'translations.*.title' => ['required', 'string', 'max:255'],
        ]);

        $parentId = $data['parent_id'] ?? null;

        if ($parentId == $item->id) {
            $parentId = null; // Prevent self-parenting
        }

        $item->update([
            'parent_id' => $parentId,
            'url' => $data['url'] ?? null,
            'route' => $data['route'] ?? null,
            'target' => $data['target'] ?? '_self',
        ]);

        $item->upsertTranslations($data['translations']);

        return back()->with('toast', ['type' => 'success', 'message' => __('Menu item updated.')]);
    }

    public function destroy(Menu $menu, MenuItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('toast', ['type' => 'success', 'message' => __('Menu item deleted.')]);
    }

    public function reorder(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:menu_items,id'],
            'items.*.parent_id' => ['nullable', 'exists:menu_items,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

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
}
