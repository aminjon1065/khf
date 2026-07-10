<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Support\DefaultMenus;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Seed primary (with submenus) and footer navigation for full-site testing.
     */
    public function run(): void
    {
        DefaultMenus::ensure();

        if (MenuItem::query()->whereNotNull('parent_id')->exists()) {
            return;
        }

        MenuItem::query()->delete();

        $defaultLocale = Language::defaultCode();
        $primary = Menu::query()->where('location', 'primary')->first();
        $footer = Menu::query()->where('location', 'footer')->first();

        if (! $primary || ! $footer) {
            return;
        }

        $aboutPage = Page::query()
            ->whereHas('translations', fn ($q) => $q->where('slug', 'like', 'about-the-committee%'))
            ->first();

        $primaryTree = [
            [
                'route' => 'welcome',
                'titles' => ['tj' => 'Асосӣ', 'ru' => 'Главная', 'en' => 'Home'],
            ],
            [
                'titles' => ['tj' => 'Дар бораи мо', 'ru' => 'Об организации', 'en' => 'About'],
                'children' => array_values(array_filter([
                    [
                        'route' => 'leadership.index',
                        'titles' => ['tj' => 'Роҳбарият', 'ru' => 'Руководство', 'en' => 'Leadership'],
                    ],
                    [
                        'route' => 'structure.index',
                        'titles' => ['tj' => 'Сохтор', 'ru' => 'Структура', 'en' => 'Structure'],
                    ],
                    $aboutPage ? [
                        'route' => 'page.'.$aboutPage->id,
                        'titles' => ['tj' => 'Дар бораи Кумита', 'ru' => 'О Комитете', 'en' => 'About the Committee'],
                    ] : null,
                ])),
            ],
            [
                'titles' => ['tj' => 'Маркази матбуот', 'ru' => 'Пресс-центр', 'en' => 'Newsroom'],
                'children' => [
                    [
                        'route' => 'news.index',
                        'titles' => ['tj' => 'Хабарҳо', 'ru' => 'Новости', 'en' => 'News'],
                    ],
                    [
                        'route' => 'gallery.index',
                        'titles' => ['tj' => 'Галерея', 'ru' => 'Галерея', 'en' => 'Gallery'],
                    ],
                ],
            ],
            [
                'titles' => ['tj' => 'ҲФ ва харита', 'ru' => 'ЧС и карта', 'en' => 'Emergencies'],
                'children' => [
                    [
                        'route' => 'incidents.index',
                        'titles' => ['tj' => 'Вазъият', 'ru' => 'Обстановка', 'en' => 'Situation'],
                    ],
                    [
                        'route' => 'map.index',
                        'titles' => ['tj' => 'Харита', 'ru' => 'Карта', 'en' => 'Map'],
                    ],
                    [
                        'route' => 'guides.index',
                        'titles' => ['tj' => 'Бехатарӣ', 'ru' => 'Безопасность', 'en' => 'Safety'],
                    ],
                ],
            ],
            [
                'titles' => ['tj' => 'Хизматрасониҳо', 'ru' => 'Услуги', 'en' => 'Services'],
                'children' => [
                    [
                        'route' => 'documents.index',
                        'titles' => ['tj' => 'Ҳуҷҷатҳо', 'ru' => 'Документы', 'en' => 'Documents'],
                    ],
                    [
                        'route' => 'services.index',
                        'titles' => ['tj' => 'Хизматҳои давлатӣ', 'ru' => 'Госуслуги', 'en' => 'Public services'],
                    ],
                    [
                        'route' => 'faq.index',
                        'titles' => ['tj' => 'Саволу ҷавоб', 'ru' => 'Вопросы и ответы', 'en' => 'FAQ'],
                    ],
                    [
                        'route' => 'appeals.create',
                        'titles' => ['tj' => 'Қабулгоҳ', 'ru' => 'Приёмная', 'en' => 'Reception'],
                    ],
                    [
                        'route' => 'subscriptions.create',
                        'titles' => ['tj' => 'Обуна', 'ru' => 'Подписка', 'en' => 'Subscribe'],
                    ],
                ],
            ],
            [
                'route' => 'contacts.index',
                'titles' => ['tj' => 'Тамос', 'ru' => 'Контакты', 'en' => 'Contacts'],
            ],
        ];

        $footerItems = [
            [
                'route' => 'welcome',
                'titles' => ['tj' => 'Асосӣ', 'ru' => 'Главная', 'en' => 'Home'],
            ],
            [
                'route' => 'news.index',
                'titles' => ['tj' => 'Хабарҳо', 'ru' => 'Новости', 'en' => 'News'],
            ],
            [
                'route' => 'guides.index',
                'titles' => ['tj' => 'Бехатарӣ', 'ru' => 'Памятки', 'en' => 'Safety guides'],
            ],
            [
                'route' => 'documents.index',
                'titles' => ['tj' => 'Ҳуҷҷатҳо', 'ru' => 'Документы', 'en' => 'Documents'],
            ],
            [
                'route' => 'vacancies.index',
                'titles' => ['tj' => 'Вакансияҳо', 'ru' => 'Вакансии', 'en' => 'Vacancies'],
            ],
            [
                'route' => 'tenders.index',
                'titles' => ['tj' => 'Тендерҳо', 'ru' => 'Тендеры', 'en' => 'Tenders'],
            ],
            [
                'route' => 'polls.index',
                'titles' => ['tj' => 'Опросҳо', 'ru' => 'Опросы', 'en' => 'Polls'],
            ],
            [
                'route' => 'statistics.index',
                'titles' => ['tj' => 'Омор', 'ru' => 'Статистика', 'en' => 'Statistics'],
            ],
            [
                'route' => 'appeals.create',
                'titles' => ['tj' => 'Қабулгоҳ', 'ru' => 'Приёмная', 'en' => 'Reception'],
            ],
            [
                'route' => 'contacts.index',
                'titles' => ['tj' => 'Тамос', 'ru' => 'Контакты', 'en' => 'Contacts'],
            ],
            [
                'url' => 'https://president.tj',
                'target' => '_blank',
                'titles' => ['tj' => 'president.tj', 'ru' => 'president.tj', 'en' => 'president.tj'],
            ],
        ];

        foreach ($primaryTree as $index => $item) {
            $this->seedMenuEntry($primary, $item, $defaultLocale, null, $index + 1);
        }

        foreach ($footerItems as $index => $item) {
            $this->seedMenuEntry($footer, $item, $defaultLocale, null, $index + 1);
        }
    }

    /**
     * @param  array{route?: string|null, url?: string|null, target?: string, titles: array<string, string>, children?: list<array<string, mixed>>}  $item
     */
    private function seedMenuEntry(
        Menu $menu,
        array $item,
        string $defaultLocale,
        ?int $parentId,
        int $sortOrder,
    ): MenuItem {
        $menuItem = $menu->items()->create([
            'parent_id' => $parentId,
            'route' => $item['route'] ?? null,
            'url' => $item['url'] ?? null,
            'sort_order' => $sortOrder,
            'target' => $item['target'] ?? '_self',
        ]);

        $translations = collect($item['titles'])
            ->map(fn (string $title): array => ['title' => $title])
            ->all();

        if (! isset($translations[$defaultLocale])) {
            $translations[$defaultLocale] = ['title' => reset($item['titles']) ?: '—'];
        }

        $menuItem->upsertTranslations($translations);

        foreach ($item['children'] ?? [] as $childIndex => $child) {
            $this->seedMenuEntry($menu, $child, $defaultLocale, $menuItem->id, $childIndex + 1);
        }

        return $menuItem;
    }
}
