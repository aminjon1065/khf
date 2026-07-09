<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    public function store(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate($this->rules());

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

    public function update(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        $data = $request->validate($this->rules());

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

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $default = Language::defaultCode();
        $locales = Language::codes() ?: config('app.locales');

        $rules = [
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'url' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255', 'regex:/^(page\.\d+|entry\.[a-z_]+\.\d+|[a-z0-9_.-]+)$/i'],
            'target' => ['nullable', 'string', Rule::in(['_self', '_blank'])],
            'translations' => ['required', 'array'],
            "translations.{$default}.title" => ['required', 'string', 'max:255'],
        ];

        foreach ($locales as $locale) {
            if ($locale === $default) {
                continue;
            }

            $rules["translations.{$locale}.title"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
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
