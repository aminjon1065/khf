<?php

namespace App\Services\Public;

use App\Models\MenuItem;
use App\Support\MenuUrlResolver;
use Illuminate\Support\Collection;

/**
 * Formats CMS menus for the public portal, hiding items with no title for the active locale (§7.8).
 */
class MenuFormatter
{
    public function __construct(
        private MenuUrlResolver $urlResolver,
    ) {}

    /**
     * @param  Collection<int, MenuItem>  $items
     * @param  Collection<int, MenuItem>  $allItems
     * @return list<array{id: int, title: string, url: string|null, route: string|null, target: string|null, children: list<array<string, mixed>>}>
     */
    public function formatTree(Collection $items, Collection $allItems, string $locale): array
    {
        return $items
            ->map(fn (MenuItem $item): ?array => $this->formatItem($item, $allItems, $locale))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MenuItem>  $allItems
     * @return array{id: int, title: string, url: string|null, route: string|null, target: string|null, children: list<array<string, mixed>>}|null
     */
    private function formatItem(MenuItem $item, Collection $allItems, string $locale): ?array
    {
        $title = $this->titleForLocale($item, $locale);

        if ($title === null) {
            return null;
        }

        $children = $this->formatTree(
            $allItems->where('parent_id', $item->id),
            $allItems,
            $locale,
        );

        $resolvedUrl = $this->urlResolver->resolve($item->url, $item->route, $locale);

        return [
            'id' => $item->id,
            'title' => $title,
            'url' => $resolvedUrl,
            'route' => $item->route,
            'target' => $item->target,
            'children' => $children,
        ];
    }

    private function titleForLocale(MenuItem $item, string $locale): ?string
    {
        $translation = $item->translations->firstWhere('locale', $locale);
        $title = trim((string) ($translation?->title ?? ''));

        return $title !== '' ? $title : null;
    }
}
