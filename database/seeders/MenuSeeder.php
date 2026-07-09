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
     * Run the database seeds.
     */
    public function run(): void
    {
        DefaultMenus::ensure();

        if (MenuItem::query()->exists()) {
            return;
        }

        $defaultLocale = Language::defaultCode();
        $primary = Menu::query()->where('location', 'primary')->first();
        $footer = Menu::query()->where('location', 'footer')->first();

        if (! $primary || ! $footer) {
            return;
        }

        $aboutPage = Page::query()
            ->whereHas('translations', fn ($q) => $q->where('slug', 'like', 'about%'))
            ->first();

        $primaryItems = [
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
                'titles' => ['tj' => 'Бехатарӣ', 'ru' => 'Безопасность', 'en' => 'Safety'],
            ],
            [
                'route' => 'map.index',
                'titles' => ['tj' => 'Харита', 'ru' => 'Карта', 'en' => 'Map'],
            ],
            [
                'route' => 'contacts.index',
                'titles' => ['tj' => 'Тамос', 'ru' => 'Контакты', 'en' => 'Contacts'],
            ],
        ];

        if ($aboutPage) {
            $primaryItems[] = [
                'route' => 'page.'.$aboutPage->id,
                'titles' => ['tj' => 'Дар бораи Кумита', 'ru' => 'О Комитете', 'en' => 'About'],
            ];
        }

        $this->seedMenuItems($primary, $primaryItems, $defaultLocale);

        $footerItems = [
            [
                'route' => 'documents.index',
                'titles' => ['tj' => 'Ҳуҷҷатҳо', 'ru' => 'Документы', 'en' => 'Documents'],
            ],
            [
                'route' => 'faq.index',
                'titles' => ['tj' => 'Саволҳо', 'ru' => 'Вопросы и ответы', 'en' => 'FAQ'],
            ],
            [
                'route' => 'vacancies.index',
                'titles' => ['tj' => 'Вакансияҳо', 'ru' => 'Вакансии', 'en' => 'Vacancies'],
            ],
            [
                'route' => 'appeals.create',
                'titles' => ['tj' => 'Қабулгоҳ', 'ru' => 'Приёмная', 'en' => 'Reception'],
            ],
        ];

        $this->seedMenuItems($footer, $footerItems, $defaultLocale);
    }

    /**
     * @param  list<array{route: string, titles: array<string, string>}>  $items
     */
    private function seedMenuItems(Menu $menu, array $items, string $defaultLocale): void
    {
        foreach ($items as $index => $item) {
            $menuItem = $menu->items()->create([
                'route' => $item['route'],
                'sort_order' => $index + 1,
                'target' => '_self',
            ]);

            $translations = collect($item['titles'])
                ->map(fn (string $title, string $locale): array => ['title' => $title])
                ->all();

            if (! isset($translations[$defaultLocale])) {
                $translations[$defaultLocale] = ['title' => reset($item['titles']) ?: '—'];
            }

            $menuItem->upsertTranslations($translations);
        }
    }
}
