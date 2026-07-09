<?php

namespace App\Support;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Link targets for the CMS menu builder — page tree, sections, and collection entries.
 */
class MenuLinkCatalog
{
    public function __construct(private ContentTypeRegistry $contentTypes) {}

    /**
     * @return list<array{value: string, label: string, group: string}>
     */
    public static function sections(): array
    {
        return [
            ['value' => 'welcome', 'label' => 'Главная', 'group' => 'Основное'],
            ['value' => 'news.index', 'label' => 'Новости', 'group' => 'Пресс-центр'],
            ['value' => 'incidents.index', 'label' => 'Обстановка', 'group' => 'ЧС и карта'],
            ['value' => 'map.index', 'label' => 'Карта', 'group' => 'ЧС и карта'],
            ['value' => 'guides.index', 'label' => 'Безопасность', 'group' => 'ЧС и карта'],
            ['value' => 'documents.index', 'label' => 'Документы', 'group' => 'Документы'],
            ['value' => 'leadership.index', 'label' => 'Руководство', 'group' => 'Об организации'],
            ['value' => 'structure.index', 'label' => 'Структура', 'group' => 'Об организации'],
            ['value' => 'contacts.index', 'label' => 'Контакты', 'group' => 'Об организации'],
            ['value' => 'gallery.index', 'label' => 'Галерея', 'group' => 'Пресс-центр'],
            ['value' => 'faq.index', 'label' => 'Вопросы и ответы', 'group' => 'Услуги'],
            ['value' => 'services.index', 'label' => 'Госуслуги', 'group' => 'Услуги'],
            ['value' => 'statistics.index', 'label' => 'Статистика', 'group' => 'Услуги'],
            ['value' => 'vacancies.index', 'label' => 'Вакансии', 'group' => 'Услуги'],
            ['value' => 'tenders.index', 'label' => 'Тендеры', 'group' => 'Услуги'],
            ['value' => 'appeals.create', 'label' => 'Приёмная', 'group' => 'Услуги'],
            ['value' => 'subscriptions.create', 'label' => 'Подписка', 'group' => 'Услуги'],
        ];
    }

    /**
     * @return list<array{id: int, title: string, status: string, slugs: array<string, string>, is_home: bool, parent_id: int|null, depth: int}>
     */
    public static function pages(): array
    {
        return self::flattenPageTree(self::pageTree());
    }

    /**
     * @return list<array{id: int, title: string, is_home: bool, children: list<array<string, mixed>>}>
     */
    public static function pageTree(): array
    {
        $defaultLocale = Language::defaultCode();

        $pages = Page::query()
            ->with('translations')
            ->where('status', ContentStatus::Published)
            ->orderBy('sort_order')
            ->get();

        return self::nestPages($pages, null, $defaultLocale);
    }

    /**
     * @return list<array{handle: string, label: string, entries: list<array{id: int, title: string}>}>
     */
    public function collectionEntries(): array
    {
        $locale = Language::defaultCode();

        return collect(config('cms.menu.entry_collections', []))
            ->map(function (string $showRoute, string $handle) use ($locale): ?array {
                if (! $this->contentTypes->has($handle)) {
                    return null;
                }

                return [
                    'handle' => $handle,
                    'label' => $this->contentTypes->get($handle)->label,
                    'entries' => $this->entriesForCollection($handle, $locale),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return list<array{id: int, title: string, is_home: bool, children: list<array<string, mixed>>}>
     */
    private static function nestPages($pages, ?int $parentId, string $locale): array
    {
        return $pages
            ->where('parent_id', $parentId)
            ->map(function (Page $page) use ($pages, $locale): array {
                return [
                    'id' => $page->id,
                    'title' => $page->translation($locale)?->title ?? '—',
                    'is_home' => $page->is_home,
                    'children' => self::nestPages($pages, $page->id, $locale),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array{id: int, title: string, is_home: bool, children: list<array<string, mixed>>}>  $tree
     * @return list<array{id: int, title: string, status: string, slugs: array<string, string>, is_home: bool, parent_id: int|null, depth: int}>
     */
    private static function flattenPageTree(array $tree, ?int $parentId = null, int $depth = 0): array
    {
        $flat = [];

        foreach ($tree as $node) {
            $flat[] = [
                'id' => $node['id'],
                'title' => str_repeat('— ', $depth).$node['title'],
                'status' => ContentStatus::Published->value,
                'slugs' => [],
                'is_home' => $node['is_home'],
                'parent_id' => $parentId,
                'depth' => $depth,
            ];

            if (! empty($node['children'])) {
                $flat = array_merge($flat, self::flattenPageTree($node['children'], $node['id'], $depth + 1));
            }
        }

        return $flat;
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    private function entriesForCollection(string $handle, string $locale): array
    {
        if (! $this->contentTypes->has($handle)) {
            return [];
        }

        $type = $this->contentTypes->get($handle);
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;
        $query = $modelClass::query()->with('translations');

        if (method_exists($modelClass, 'scopePublished')) {
            /** @var Builder<Model> $query */
            $query->published();
        }

        return $query
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Model $entry): array => [
                'id' => (int) $entry->getKey(),
                'title' => $this->entryTitle($entry, $locale, $type),
            ])
            ->all();
    }

    private function entryTitle(Model $model, string $locale, ContentTypeDefinition $type): string
    {
        $translation = method_exists($model, 'translation') ? $model->translation($locale) : null;

        foreach ([$type->listSearchField, 'title', 'name', 'question', 'label'] as $field) {
            $value = $translation?->getAttribute($field);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$model->getKey();
    }
}
