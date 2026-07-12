<?php

namespace Database\Seeders;

use App\Enums\AlertStatus;
use App\Enums\AppealStatus;
use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Enums\EmploymentType;
use App\Enums\GuideAudience;
use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\PollType;
use App\Enums\PostType;
use App\Enums\ServiceCategory;
use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTopic;
use App\Enums\TenderType;
use App\Models\Alert;
use App\Models\Appeal;
use App\Models\Category;
use App\Models\Document;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GovService;
use App\Models\Guide;
use App\Models\Incident;
use App\Models\Leader;
use App\Models\Page;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Post;
use App\Models\Region;
use App\Models\Statistic;
use App\Models\Subdivision;
use App\Models\Subscriber;
use App\Models\Tag;
use App\Models\Tender;
use App\Models\TouristGroup;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\Concerns\SeedsTrilingualContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Full CMS + portal dataset for local/manual testing after migrate:fresh --seed.
 *
 * Covers every content collection with published items, plus draft / moderation /
 * archived / trashed samples so Entry Browser filters and forms can be exercised.
 * Idempotent — safe to re-run (skips sections that already have rows).
 */
class TestContentSeeder extends Seeder
{
    use SeedsTrilingualContent;

    public function run(): void
    {
        $author = User::query()->where('email', 'aminjon1065@gmail.com')->first()
            ?? User::query()->first();
        /** @var Collection<string, int> $regions */
        $regions = Region::query()->pluck('id', 'code');

        $categories = $this->seedCategories();
        $tags = $this->seedTags();

        $this->seedPosts($author?->id, $categories, $tags);
        $this->seedPages();
        $this->seedDocuments($tags);
        $this->seedGuides();
        $this->seedGalleries();
        $this->seedFaqs();
        $this->seedPolls();
        $this->seedGovServices();
        $this->seedStatistics();
        $this->seedLeaders();
        $this->seedSubdivisions();
        $this->seedVacancies();
        $this->seedTenders();
        $this->seedIncidents($regions);
        $this->seedAlerts($regions);
        $this->seedInbox($regions);
    }

    /**
     * @return array<string, int>
     */
    private function seedCategories(): array
    {
        $defs = [
            'civil-defense' => ['tj' => 'Мудофиаи гражданӣ', 'ru' => 'Гражданская оборона', 'en' => 'Civil defense'],
            'prevention' => ['tj' => 'Пешгирии ҲФ', 'ru' => 'Профилактика ЧС', 'en' => 'Emergency prevention'],
            'cooperation' => ['tj' => 'Ҳамкории байналмилалӣ', 'ru' => 'Международное сотрудничество', 'en' => 'International cooperation'],
        ];

        $map = [];
        $order = 1;

        foreach ($defs as $key => $names) {
            $category = Category::query()
                ->whereHas('translations', fn ($q) => $q->where('slug', $key.'-en'))
                ->first();

            if ($category === null) {
                $category = Category::create(['sort_order' => $order]);
                $category->upsertTranslations(
                    $this->trilingual($names, fn (string $locale, string $name): array => [
                        'name' => $name,
                        'slug' => $key.'-'.$locale,
                    ]),
                );
            }

            $map[$key] = $category->id;
            $order++;
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    private function seedTags(): array
    {
        if (Tag::query()->exists()) {
            return Tag::query()->orderBy('id')->pluck('id')->all();
        }

        $defs = [
            'alert' => ['tj' => 'Огоҳинома', 'ru' => 'Оповещение', 'en' => 'Alert'],
            'drill' => ['tj' => 'Машқ', 'ru' => 'Учения', 'en' => 'Drill'],
            'rescue' => ['tj' => 'Наҷот', 'ru' => 'Спасение', 'en' => 'Rescue'],
        ];

        $ids = [];

        foreach ($defs as $key => $names) {
            $tag = Tag::create();
            $tag->upsertTranslations(
                $this->trilingual($names, fn (string $locale, string $name): array => [
                    'name' => $name,
                    'slug' => $key.'-'.$locale,
                ]),
            );
            $ids[] = $tag->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $categories
     * @param  list<int>  $tagIds
     */
    private function seedPosts(?int $authorId, array $categories, array $tagIds): void
    {
        if (Post::withTrashed()->exists()) {
            return;
        }

        $posts = [
            [
                'type' => PostType::PressRelease,
                'category' => 'civil-defense',
                'status' => ContentStatus::Published,
                'days' => 0,
                'tj' => ['title' => 'Дар Душанбе машқҳои мудофиаи гражданӣ гузаронида шуданд', 'excerpt' => 'Амалҳои аҳолӣ ҳангоми сигнали «Диққат ба ҳама!» машқ карда шуданд.'],
                'ru' => ['title' => 'Учения по гражданской обороне прошли в Душанбе', 'excerpt' => 'Отработаны действия населения при сигнале «Внимание всем!».'],
                'en' => ['title' => 'Civil-defense drills held in Dushanbe', 'excerpt' => 'Public response to the Attention all! signal was practised.'],
            ],
            [
                'type' => PostType::Summary,
                'category' => 'prevention',
                'status' => ContentStatus::Published,
                'days' => 2,
                'tj' => ['title' => 'Сатҳи оби дарёи Вахш ба эътидол омад', 'excerpt' => 'Хатари обхезии соҳилҳо бартараф шуд.'],
                'ru' => ['title' => 'Уровень воды в реке Вахш стабилизировался', 'excerpt' => 'Угроза подтопления прибрежных участков снята.'],
                'en' => ['title' => 'Water level on the Vakhsh river has stabilised', 'excerpt' => 'The risk of flooding along the banks has been lifted.'],
            ],
            [
                'type' => PostType::Announcement,
                'category' => 'prevention',
                'status' => ContentStatus::Published,
                'days' => 4,
                'tj' => ['title' => 'Дастурамал: ҳангоми заминҷунбӣ чӣ бояд кард', 'excerpt' => 'Қоидаҳои рафтор пеш аз, ҳангом ва пас аз ларзиш.'],
                'ru' => ['title' => 'Памятка: как действовать при землетрясении', 'excerpt' => 'Правила поведения до, во время и после толчков.'],
                'en' => ['title' => 'Guide: what to do during an earthquake', 'excerpt' => 'Rules of conduct before, during and after tremors.'],
            ],
            [
                'type' => PostType::News,
                'category' => 'civil-defense',
                'status' => ContentStatus::Published,
                'days' => 6,
                'tj' => ['title' => 'Дар водии Рашт пости наҷотдиҳии нав кушода шуд', 'excerpt' => 'Пост бо техникаи корҳои наҷотдиҳии кӯҳӣ муҷаҳҳаз аст.'],
                'ru' => ['title' => 'Открыт новый спасательный пост в Раштской долине', 'excerpt' => 'Пост оснащён техникой для горноспасательных работ.'],
                'en' => ['title' => 'A new rescue post opened in the Rasht valley', 'excerpt' => 'The post is equipped for mountain rescue work.'],
            ],
            [
                'type' => PostType::PressRelease,
                'category' => 'cooperation',
                'status' => ContentStatus::Published,
                'days' => 8,
                'tj' => ['title' => 'Созишнома оид ба мониторинги фаромарзии обхезиҳо имзо шуд', 'excerpt' => 'Мубодилаи додаҳои гидрометеорологӣ тақвият меёбад.'],
                'ru' => ['title' => 'Подписано соглашение о трансграничном мониторинге паводков', 'excerpt' => 'Усиливается обмен гидрометеоданными в регионе.'],
                'en' => ['title' => 'Agreement on transboundary flood monitoring signed', 'excerpt' => 'Regional hydrometeorological data exchange is strengthened.'],
            ],
            [
                'type' => PostType::News,
                'category' => 'prevention',
                'status' => ContentStatus::Draft,
                'days' => null,
                'tj' => ['title' => '[DRAFT] Маводи санҷишӣ — сиёҳнавис', 'excerpt' => 'Барои санҷиши филтри draft дар Entry Browser.'],
                'ru' => ['title' => '[DRAFT] Тестовый материал — черновик', 'excerpt' => 'Для проверки фильтра draft в Entry Browser.'],
                'en' => ['title' => '[DRAFT] Test material — draft', 'excerpt' => 'For testing the draft filter in Entry Browser.'],
            ],
            [
                'type' => PostType::Announcement,
                'category' => 'prevention',
                'status' => ContentStatus::Moderation,
                'days' => null,
                'tj' => ['title' => '[MOD] Маводи санҷишӣ — модератсия', 'excerpt' => 'Барои санҷиши ҳолати moderation.'],
                'ru' => ['title' => '[MOD] Тестовый материал — модерация', 'excerpt' => 'Для проверки статуса moderation.'],
                'en' => ['title' => '[MOD] Test material — moderation', 'excerpt' => 'For testing moderation status.'],
            ],
        ];

        foreach ($posts as $index => $data) {
            $post = Post::create([
                'type' => $data['type'],
                'category_id' => $categories[$data['category']] ?? null,
                'author_id' => $authorId,
                'status' => $data['status'],
                'published_at' => $data['status'] === ContentStatus::Published
                    ? now()->subDays((int) $data['days'])
                    : null,
            ]);

            $post->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                    'excerpt' => $t['excerpt'],
                    'body' => '<p>'.$t['excerpt'].'</p><p>'.$t['excerpt'].'</p>',
                ];
            }));

            if ($tagIds !== [] && $data['status'] === ContentStatus::Published) {
                $post->tags()->sync([$tagIds[$index % count($tagIds)]]);
            }

            if ($data['status'] === ContentStatus::Published) {
                $this->attachPublicImage($post, Post::COVER_COLLECTION);
            }
        }

        $trashed = Post::create([
            'type' => PostType::News,
            'category_id' => $categories['prevention'] ?? null,
            'author_id' => $authorId,
            'status' => ContentStatus::Archived,
            'published_at' => now()->subDays(30),
        ]);
        $trashed->upsertTranslations($this->trilingual([
            'tj' => ['title' => '[TRASH] Маводи нестшуда', 'excerpt' => 'Барои санҷиши trash.'],
            'ru' => ['title' => '[TRASH] Удалённый материал', 'excerpt' => 'Для проверки корзины.'],
            'en' => ['title' => '[TRASH] Deleted material', 'excerpt' => 'For testing trash.'],
        ], fn (string $locale, array $t): array => [
            'title' => $t['title'],
            'slug' => $this->slugFor('Deleted material', $locale, 'trash'),
            'excerpt' => $t['excerpt'],
            'body' => '<p>'.$t['excerpt'].'</p>',
        ]));
        $trashed->delete();
    }

    private function seedPages(): void
    {
        if (Page::withTrashed()->exists()) {
            return;
        }

        $pages = [
            [
                'home' => true,
                'sort' => 1,
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Саҳифаи асосӣ', 'body' => 'Саҳифаи хонагӣ бо блокҳои портал.'],
                'ru' => ['title' => 'Главная страница', 'body' => 'Домашняя страница с блоками портала.'],
                'en' => ['title' => 'Home page', 'body' => 'Homepage with portal blocks.'],
                'blocks' => [
                    ['type' => 'text', 'data' => ['body' => '<p>Welcome to CoES RT.</p>']],
                    ['type' => 'cta', 'data' => ['title' => '112', 'url' => 'tel:112']],
                ],
            ],
            [
                'home' => false,
                'sort' => 2,
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Дар бораи Кумита', 'body' => 'Кумитаи ҳолатҳои фавқулодда ва мудофиаи гражданӣ мақоми ваколатдори давлатӣ аст.'],
                'ru' => ['title' => 'О Комитете', 'body' => 'Комитет по чрезвычайным ситуациям и гражданской обороне — уполномоченный государственный орган.'],
                'en' => ['title' => 'About the Committee', 'body' => 'The Committee for Emergency Situations and Civil Defense is the authorised state body.'],
                'blocks' => [],
            ],
            [
                'home' => false,
                'sort' => 3,
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Фаъолият', 'body' => 'Пешгирӣ ва бартарафсозии ҳолатҳои фавқулодда, мудофиаи гражданӣ, корҳои наҷотдиҳӣ.'],
                'ru' => ['title' => 'Деятельность', 'body' => 'Предупреждение и ликвидация ЧС, гражданская оборона, спасательные работы.'],
                'en' => ['title' => 'Activities', 'body' => 'Prevention and response to emergencies, civil defense, rescue operations.'],
                'blocks' => [],
            ],
            [
                'home' => false,
                'sort' => 4,
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Тамос', 'body' => 'Телефони ягонаи боварӣ: 112. Душанбе, Ҷумҳурии Тоҷикистон.'],
                'ru' => ['title' => 'Контакты', 'body' => 'Единый телефон доверия: 112. г. Душанбе, Республика Таджикистан.'],
                'en' => ['title' => 'Contacts', 'body' => 'Unified helpline: 112. Dushanbe, Republic of Tajikistan.'],
                'blocks' => [
                    ['type' => 'contacts', 'data' => ['phone' => '112', 'email' => 'info@khf.tj']],
                ],
            ],
            [
                'home' => false,
                'sort' => 90,
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Сиёсати махфият', 'body' => 'Маълумоти шахсӣ мувофиқи қонунгузории ҶТ коркард мегардад.'],
                'ru' => ['title' => 'Политика конфиденциальности', 'body' => 'Персональные данные обрабатываются в соответствии с законодательством РТ.'],
                'en' => ['title' => 'Privacy policy', 'body' => 'Personal data is processed in accordance with the legislation of the RT.'],
                'blocks' => [],
            ],
            [
                'home' => false,
                'sort' => 99,
                'status' => ContentStatus::Draft,
                'tj' => ['title' => '[DRAFT] Саҳифаи санҷишӣ', 'body' => 'Барои санҷиши draft.'],
                'ru' => ['title' => '[DRAFT] Тестовая страница', 'body' => 'Для проверки draft.'],
                'en' => ['title' => '[DRAFT] Test page', 'body' => 'For draft testing.'],
                'blocks' => [],
            ],
        ];

        foreach ($pages as $data) {
            $page = Page::create([
                'parent_id' => null,
                'status' => $data['status'],
                'sort_order' => $data['sort'],
                'is_home' => $data['home'],
            ]);

            $page->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                    'content' => '<p>'.$t['body'].'</p>',
                    'blocks' => $data['blocks'],
                ];
            }));
        }
    }

    /**
     * @param  list<int>  $tagIds
     */
    private function seedDocuments(array $tagIds): void
    {
        if (Document::withTrashed()->exists()) {
            return;
        }

        $docs = [
            [
                'type' => DocumentType::Law,
                'status' => ContentStatus::Published,
                'days' => 30,
                'tj' => ['name' => 'Қонуни ҶТ «Дар бораи ҳифзи аҳолӣ ва ҳудуд аз ҳолатҳои фавқулодда»'],
                'ru' => ['name' => 'Закон РТ «О защите населения и территорий от чрезвычайных ситуаций»'],
                'en' => ['name' => 'Law of the RT On protection of population and territories from emergencies'],
            ],
            [
                'type' => DocumentType::Plan,
                'status' => ContentStatus::Published,
                'days' => 44,
                'tj' => ['name' => 'Нақшаи чорабиниҳои асосии мудофиаи гражданӣ барои соли 2026'],
                'ru' => ['name' => 'План основных мероприятий в области гражданской обороны на 2026 год'],
                'en' => ['name' => 'Plan of principal civil-defense measures for 2026'],
            ],
            [
                'type' => DocumentType::Form,
                'status' => ContentStatus::Published,
                'days' => 56,
                'tj' => ['name' => 'Шакли огоҳинома дар бораи баромадани гурӯҳи сайёҳӣ ба хатсайр'],
                'ru' => ['name' => 'Форма уведомления о выходе туристской группы на маршрут'],
                'en' => ['name' => 'Tourist-group route-departure notification form'],
            ],
            [
                'type' => DocumentType::Report,
                'status' => ContentStatus::Draft,
                'days' => 70,
                'tj' => ['name' => '[DRAFT] Ҳисоботи санҷишӣ'],
                'ru' => ['name' => '[DRAFT] Тестовый отчёт'],
                'en' => ['name' => '[DRAFT] Test report'],
            ],
        ];

        foreach ($docs as $data) {
            $document = Document::create([
                'type' => $data['type'],
                'source' => 'КЧС',
                'document_date' => now()->subDays($data['days']),
                'status' => $data['status'],
                'sort_order' => 0,
            ]);

            $document->upsertTranslations($this->trilingual($data, fn (string $locale, array $t): array => [
                'name' => $t['name'],
                'description' => null,
            ]));

            if ($data['status'] === ContentStatus::Published) {
                $this->attachPdf($document, Document::FILES_COLLECTION, $this->slugFor($data['en']['name'], 'en').'.pdf');
            }

            if ($tagIds !== [] && $data['status'] === ContentStatus::Published) {
                $document->tags()->sync([$tagIds[0]]);
            }
        }
    }

    private function seedGuides(): void
    {
        if (Guide::withTrashed()->exists()) {
            return;
        }

        $guides = [
            [
                'hazard' => IncidentType::Earthquake,
                'audience' => GuideAudience::General,
                'tj' => ['title' => 'Ҳангоми заминҷунбӣ чӣ бояд кард', 'summary' => 'Қоидаҳои рафтор пеш аз, ҳангом ва пас аз ларзиш.', 'content' => '<ul><li>Оромиро нигоҳ доред.</li><li>Зери миз пинҳон шавед.</li></ul>'],
                'ru' => ['title' => 'Как действовать при землетрясении', 'summary' => 'Правила поведения до, во время и после толчков.', 'content' => '<ul><li>Сохраняйте спокойствие.</li><li>Укройтесь под столом.</li></ul>'],
                'en' => ['title' => 'What to do during an earthquake', 'summary' => 'Rules before, during and after tremors.', 'content' => '<ul><li>Stay calm.</li><li>Take cover under a table.</li></ul>'],
            ],
            [
                'hazard' => IncidentType::Flood,
                'audience' => GuideAudience::General,
                'tj' => ['title' => 'Ҳангоми обхезӣ ва сел', 'summary' => 'Чӣ тавр аз минтақаи хатарнок дур шудан.', 'content' => '<ul><li>Ба ҷойҳои баланд гузаред.</li></ul>'],
                'ru' => ['title' => 'При наводнении и селе', 'summary' => 'Как уйти из опасной зоны.', 'content' => '<ul><li>Поднимитесь на возвышенность.</li></ul>'],
                'en' => ['title' => 'During a flood or mudflow', 'summary' => 'How to leave a danger zone.', 'content' => '<ul><li>Move to higher ground.</li></ul>'],
            ],
            [
                'hazard' => IncidentType::Fire,
                'audience' => GuideAudience::General,
                'tj' => ['title' => 'Ҳангоми сӯхтор', 'summary' => 'Амалҳои аввалия ҳангоми оташсӯзӣ.', 'content' => '<ul><li>Ба 101 занг занед.</li></ul>'],
                'ru' => ['title' => 'При пожаре', 'summary' => 'Первоочередные действия при возгорании.', 'content' => '<ul><li>Позвоните 101.</li></ul>'],
                'en' => ['title' => 'In case of fire', 'summary' => 'First actions in a fire.', 'content' => '<ul><li>Call 101.</li></ul>'],
            ],
            [
                'hazard' => IncidentType::Earthquake,
                'audience' => GuideAudience::Children,
                'tj' => ['title' => 'Заминҷунбӣ: барои хонандагон', 'summary' => 'Дастур барои мактаббачагон.', 'content' => '<ul><li>Натарс, ором бош.</li></ul>'],
                'ru' => ['title' => 'Землетрясение: для школьников', 'summary' => 'Памятка для детей.', 'content' => '<ul><li>Не бойся, сохраняй спокойствие.</li></ul>'],
                'en' => ['title' => 'Earthquake: for pupils', 'summary' => 'A memo for children.', 'content' => '<ul><li>Don’t panic, stay calm.</li></ul>'],
            ],
        ];

        foreach ($guides as $i => $data) {
            $guide = Guide::create([
                'hazard_type' => $data['hazard'],
                'audience' => $data['audience'],
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
            ]);

            $guide->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                    'summary' => $t['summary'],
                    'content' => $t['content'],
                ];
            }));
        }
    }

    private function seedGalleries(): void
    {
        if (Gallery::query()->exists()) {
            return;
        }

        $albums = [
            [
                'tj' => ['title' => 'Машқҳои мудофиаи гражданӣ', 'description' => 'Суратҳо аз машқҳои ҷамъиятӣ дар Душанбе.'],
                'ru' => ['title' => 'Учения по гражданской обороне', 'description' => 'Фотоматериалы учений в Душанбе.'],
                'en' => ['title' => 'Civil defense drills', 'description' => 'Photos from public drills in Dushanbe.'],
            ],
            [
                'tj' => ['title' => 'Корҳои наҷотдиҳӣ дар кӯҳҳо', 'description' => 'Амалияҳои наҷотдиҳӣ дар минтақаи ВМКБ.'],
                'ru' => ['title' => 'Горноспасательные работы', 'description' => 'Спасательные операции в зоне ГБАО.'],
                'en' => ['title' => 'Mountain rescue operations', 'description' => 'Rescue operations in the GBAO region.'],
            ],
        ];

        foreach ($albums as $i => $data) {
            $gallery = Gallery::create([
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
            ]);

            $gallery->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                    'description' => $t['description'],
                ];
            }));

            foreach (['emblem-tj.webp', 'president.webp'] as $file) {
                $this->attachPublicImage($gallery, Gallery::PHOTOS_COLLECTION, $file, preserveOriginal: true);
            }
        }
    }

    private function seedFaqs(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $items = [
            [
                'tj' => ['question' => 'Ба кадом рақам занг занам дар ҳолати фавқулодда?', 'answer' => 'Ба рақами ягонаи боварии 112 занг занед.'],
                'ru' => ['question' => 'По какому номеру звонить в чрезвычайной ситуации?', 'answer' => 'Звоните по единому номеру доверия 112.'],
                'en' => ['question' => 'Which number should I call in an emergency?', 'answer' => 'Call the unified helpline 112.'],
            ],
            [
                'tj' => ['question' => 'Чӣ тавр гурӯҳи сайёҳиро ба қайд гирам?', 'answer' => 'Дар бахши «Сайёҳӣ» шакли бақайдгирӣро пур кунед.'],
                'ru' => ['question' => 'Как зарегистрировать туристическую группу?', 'answer' => 'Заполните форму в разделе «Туризм» на сайте.'],
                'en' => ['question' => 'How do I register a tourist group?', 'answer' => 'Fill in the form in the Tourism section.'],
            ],
            [
                'tj' => ['question' => 'Огоҳиномаҳо чӣ гуна расонида мешаванд?', 'answer' => 'Шумо метавонед ба хабарномаи почта ё огоҳиномаҳои браузерӣ обуна шавед.'],
                'ru' => ['question' => 'Как доставляются оповещения?', 'answer' => 'Подпишитесь на e-mail или браузерные уведомления.'],
                'en' => ['question' => 'How are alerts delivered?', 'answer' => 'Subscribe to email or browser notifications.'],
            ],
        ];

        foreach ($items as $i => $data) {
            $faq = Faq::create([
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
            ]);

            $faq->upsertTranslations($this->trilingual($data, fn (string $locale, array $t): array => [
                'question' => $t['question'],
                'answer' => '<p>'.$t['answer'].'</p>',
            ]));
        }
    }

    private function seedPolls(): void
    {
        if (Poll::query()->exists()) {
            return;
        }

        $polls = [
            [
                'type' => PollType::General,
                'options' => [
                    'tj' => ['Хеле муфид', 'Муфид', 'Номуфид'],
                    'ru' => ['Очень полезно', 'Полезно', 'Не полезно'],
                    'en' => ['Very useful', 'Useful', 'Not useful'],
                ],
                'tj' => ['title' => 'Сомонаи расмии Кумита чӣ қадар муфид аст?', 'description' => 'Баҳо диҳед.'],
                'ru' => ['title' => 'Насколько полезен официальный сайт Комитета?', 'description' => 'Оцените.'],
                'en' => ['title' => 'How useful is the Committee official website?', 'description' => 'Rate it.'],
            ],
            [
                'type' => PollType::AntiCorruptionExpertise,
                'options' => [
                    'tj' => ['Пурра', 'Қисман', 'Нақшан не'],
                    'ru' => ['Полностью', 'Частично', 'Практически нет'],
                    'en' => ['Fully', 'Partially', 'Hardly at all'],
                ],
                'tj' => ['title' => 'Огоҳиномаҳои оид ба фасод', 'description' => 'Оё шумо огоҳ ҳастед?'],
                'ru' => ['title' => 'Антикоррупционная грамотность', 'description' => 'Насколько вы осведомлены?'],
                'en' => ['title' => 'Anti-corruption awareness', 'description' => 'How informed are you?'],
            ],
        ];

        foreach ($polls as $i => $data) {
            $poll = Poll::create([
                'type' => $data['type'],
                'status' => ContentStatus::Published,
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addMonth(),
                'show_results' => true,
                'sort_order' => $i + 1,
            ]);

            $poll->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'description' => $t['description'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                ];
            }));

            foreach ($data['options']['tj'] as $optionIndex => $_) {
                $option = PollOption::create([
                    'poll_id' => $poll->id,
                    'sort_order' => $optionIndex + 1,
                ]);

                $option->upsertTranslations([
                    'tj' => ['label' => $data['options']['tj'][$optionIndex]],
                    'ru' => ['label' => $data['options']['ru'][$optionIndex]],
                    'en' => ['label' => $data['options']['en'][$optionIndex]],
                ]);
            }
        }
    }

    private function seedGovServices(): void
    {
        if (GovService::query()->exists()) {
            return;
        }

        $services = [
            [
                'category' => ServiceCategory::Information,
                'online' => true,
                'tj' => ['title' => 'Маълумот оид ба ҳолатҳои фавқулодда'],
                'ru' => ['title' => 'Справка об обстановке ЧС'],
                'en' => ['title' => 'Emergency situation information'],
            ],
            [
                'category' => ServiceCategory::Registration,
                'online' => true,
                'tj' => ['title' => 'Бақайдгирии гурӯҳи сайёҳӣ'],
                'ru' => ['title' => 'Регистрация туристической группы'],
                'en' => ['title' => 'Tourist group registration'],
            ],
            [
                'category' => ServiceCategory::Consultation,
                'online' => false,
                'tj' => ['title' => 'Машварати оид ба омодагии фавқулодда'],
                'ru' => ['title' => 'Консультация по готовности к ЧС'],
                'en' => ['title' => 'Emergency preparedness consultation'],
            ],
        ];

        foreach ($services as $i => $data) {
            $service = GovService::create([
                'category' => $data['category'],
                'status' => ContentStatus::Published,
                'is_online' => $data['online'],
                'processing_time' => '3–5 working days',
                'fee' => 'Free',
                'sort_order' => $i + 1,
            ]);

            $service->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                    'summary' => $t['title'],
                    'description' => '<p>'.$t['title'].'</p>',
                    'eligibility' => $locale === 'en' ? 'Citizens and organisations.' : ($locale === 'ru' ? 'Граждане и организации.' : 'Шаҳрвандон ва ташкилотҳо.'),
                    'required_documents' => $locale === 'en' ? 'Application form and ID.' : ($locale === 'ru' ? 'Заявление и удостоверение личности.' : 'Ариза ва шиноснома.'),
                ];
            }));
        }
    }

    private function seedStatistics(): void
    {
        if (Statistic::query()->exists()) {
            return;
        }

        $stats = [
            ['value' => '128', 'year' => 2025, 'tj' => 'Амалиётҳои наҷот', 'ru' => 'Спасательные операции', 'en' => 'Rescue operations', 'unit' => null],
            ['value' => '42', 'year' => 2025, 'tj' => 'Машқҳои мудофиаи гражданӣ', 'ru' => 'Учения по ГО', 'en' => 'Civil defense drills', 'unit' => null],
            ['value' => '15600', 'year' => 2025, 'tj' => 'Огоҳшудагон', 'ru' => 'Оповещённые граждане', 'en' => 'Citizens alerted', 'unit' => 'people'],
        ];

        foreach ($stats as $i => $data) {
            $stat = Statistic::create([
                'status' => ContentStatus::Published,
                'value' => $data['value'],
                'year' => $data['year'],
                'sort_order' => $i + 1,
            ]);

            $stat->upsertTranslations([
                'tj' => ['label' => $data['tj'], 'unit' => $data['unit']],
                'ru' => ['label' => $data['ru'], 'unit' => $data['unit'] === 'people' ? 'чел.' : null],
                'en' => ['label' => $data['en'], 'unit' => $data['unit']],
            ]);
        }
    }

    private function seedLeaders(): void
    {
        if (Leader::query()->exists()) {
            return;
        }

        $leaders = [
            [
                'email' => 'chairman@khf.tj',
                'phone' => '+992 37 221-00-01',
                'tj' => ['full_name' => 'Қодиров Фаррух Абдулло', 'position' => 'Раиси Кумита', 'bio' => 'Роҳбари Кумита.'],
                'ru' => ['full_name' => 'Кодиров Фаррух Абдулло', 'position' => 'Председатель Комитета', 'bio' => 'Руководитель Комитета.'],
                'en' => ['full_name' => 'Kodirov Farrux Abdullo', 'position' => 'Chairman of the Committee', 'bio' => 'Head of the Committee.'],
            ],
            [
                'email' => 'deputy@khf.tj',
                'phone' => '+992 37 221-00-02',
                'tj' => ['full_name' => 'Раҳимова Гулнора Саид', 'position' => 'Ҷонишини раис', 'bio' => 'Ҳамоҳангсозии амалияҳои наҷотдиҳӣ.'],
                'ru' => ['full_name' => 'Рахимова Гулнора Саид', 'position' => 'Заместитель председателя', 'bio' => 'Координация спасательных операций.'],
                'en' => ['full_name' => 'Rahimova Gulnora Said', 'position' => 'Deputy Chairman', 'bio' => 'Coordinates rescue operations.'],
            ],
            [
                'email' => 'press@khf.tj',
                'phone' => '+992 37 221-00-03',
                'tj' => ['full_name' => 'Назаров Фирӯз Ҷӯра', 'position' => 'Мушовири раис', 'bio' => 'Робита бо омма ва матбуот.'],
                'ru' => ['full_name' => 'Назаров Фируз Джура', 'position' => 'Советник председателя', 'bio' => 'Связи с общественностью.'],
                'en' => ['full_name' => 'Nazarov Firuz Jura', 'position' => 'Advisor to the Chairman', 'bio' => 'Public relations.'],
            ],
        ];

        foreach ($leaders as $i => $data) {
            $leader = Leader::create([
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            $leader->upsertTranslations($this->trilingual($data, fn (string $locale, array $t): array => [
                'full_name' => $t['full_name'],
                'position' => $t['position'],
                'bio' => $t['bio'],
                'reception' => $locale === 'en' ? 'Mon, Wed: 9:00–12:00' : ($locale === 'ru' ? 'Пн, Ср: 9:00–12:00' : 'Дш, Чш: 9:00–12:00'),
            ]));

            $this->attachPublicImage($leader, Leader::PHOTO_COLLECTION, 'president.webp');
        }
    }

    private function seedSubdivisions(): void
    {
        if (Subdivision::query()->exists()) {
            return;
        }

        $hq = Subdivision::create([
            'status' => ContentStatus::Published,
            'parent_id' => null,
            'sort_order' => 1,
            'email' => 'info@khf.tj',
            'phone' => '+992 37 221-00-00',
            'staff_count' => 120,
        ]);

        $hq->upsertTranslations([
            'tj' => ['name' => 'Аппарати марказии Кумита', 'head' => 'Қодиров Ф.А.', 'functions' => 'Идоракунии умумии фаъолияти Кумита.', 'address' => 'ш. Душанбе, кӯчаи Айнӣ, 14'],
            'ru' => ['name' => 'Центральный аппарат Комитета', 'head' => 'Кодиров Ф.А.', 'functions' => 'Общее руководство деятельностью Комитета.', 'address' => 'г. Душанбе, ул. Айни, 14'],
            'en' => ['name' => 'Central office of the Committee', 'head' => 'Kodirov F.A.', 'functions' => 'General management of the Committee.', 'address' => 'Dushanbe, Ayni str., 14'],
        ]);

        $children = [
            [
                'tj' => ['name' => 'Идораи омодагии фавқулодда', 'head' => 'Назаров Ф.Ҷ.', 'functions' => 'Назорати омодагӣ ва огоҳонии аҳолӣ.', 'address' => 'ш. Душанбе'],
                'ru' => ['name' => 'Управление готовности к ЧС', 'head' => 'Назаров Ф.Д.', 'functions' => 'Контроль готовности и оповещения населения.', 'address' => 'г. Душанбе'],
                'en' => ['name' => 'Emergency preparedness department', 'head' => 'Nazarov F.J.', 'functions' => 'Readiness monitoring and public alerting.', 'address' => 'Dushanbe'],
            ],
            [
                'tj' => ['name' => 'Идораи корҳои наҷотдиҳӣ', 'head' => 'Раҳимова Г.С.', 'functions' => 'Ташкили амалияҳои наҷот.', 'address' => 'ш. Душанбе'],
                'ru' => ['name' => 'Управление спасательных работ', 'head' => 'Рахимова Г.С.', 'functions' => 'Организация спасательных работ.', 'address' => 'г. Душанбе'],
                'en' => ['name' => 'Rescue operations department', 'head' => 'Rahimova G.S.', 'functions' => 'Organisation of rescue response.', 'address' => 'Dushanbe'],
            ],
            [
                'tj' => ['name' => 'Маркази матбуот', 'head' => 'Сафарова Н.А.', 'functions' => 'Хабарҳо ва робита бо омма.', 'address' => 'ш. Душанбе'],
                'ru' => ['name' => 'Пресс-центр', 'head' => 'Сафарова Н.А.', 'functions' => 'Новости и связи с общественностью.', 'address' => 'г. Душанбе'],
                'en' => ['name' => 'Press centre', 'head' => 'Safarova N.A.', 'functions' => 'News and public relations.', 'address' => 'Dushanbe'],
            ],
        ];

        foreach ($children as $i => $translations) {
            $child = Subdivision::create([
                'status' => ContentStatus::Published,
                'parent_id' => $hq->id,
                'sort_order' => $i + 1,
                'email' => 'dept'.($i + 1).'@khf.tj',
                'phone' => '+992 37 221-00-'.str_pad((string) ($i + 10), 2, '0', STR_PAD_LEFT),
                'staff_count' => 25 + ($i * 10),
            ]);

            $child->upsertTranslations($translations);
        }
    }

    private function seedVacancies(): void
    {
        if (Vacancy::withTrashed()->exists()) {
            return;
        }

        $vacancies = [
            [
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Мутахассиси IT', 'department' => 'Идораи технология', 'location' => 'Душанбе'],
                'ru' => ['title' => 'IT-специалист', 'department' => 'Управление технологий', 'location' => 'Душанбе'],
                'en' => ['title' => 'IT specialist', 'department' => 'Technology department', 'location' => 'Dushanbe'],
            ],
            [
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Корманди наҷотдиҳӣ', 'department' => 'Идораи наҷот', 'location' => 'Хатлон'],
                'ru' => ['title' => 'Спасатель', 'department' => 'Управление спасения', 'location' => 'Хатлон'],
                'en' => ['title' => 'Rescue worker', 'department' => 'Rescue department', 'location' => 'Khatlon'],
            ],
            [
                'status' => ContentStatus::Draft,
                'tj' => ['title' => '[DRAFT] Вакансияи санҷишӣ', 'department' => 'Тест', 'location' => 'Душанбе'],
                'ru' => ['title' => '[DRAFT] Тестовая вакансия', 'department' => 'Тест', 'location' => 'Душанбе'],
                'en' => ['title' => '[DRAFT] Test vacancy', 'department' => 'Test', 'location' => 'Dushanbe'],
            ],
        ];

        foreach ($vacancies as $data) {
            $vacancy = Vacancy::create([
                'status' => $data['status'],
                'employment_type' => EmploymentType::FullTime,
                'positions_count' => 1,
                'published_at' => $data['status'] === ContentStatus::Published ? now()->subDays(2) : null,
                'deadline_at' => now()->addDays(21),
            ]);

            $vacancy->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                    'department' => $t['department'],
                    'location' => $t['location'],
                    'summary' => $t['title'],
                    'description' => '<p>'.$t['title'].'</p>',
                    'requirements' => '<ul><li>'.($locale === 'en' ? 'Relevant experience' : ($locale === 'ru' ? 'Соответствующий опыт' : 'Таҷрибаи мувофиқ')).'</li></ul>',
                    'responsibilities' => '<ul><li>'.($locale === 'en' ? 'Daily duties' : ($locale === 'ru' ? 'Ежедневные обязанности' : 'Вазифаҳои ҳаррӯза')).'</li></ul>',
                ];
            }));
        }
    }

    private function seedTenders(): void
    {
        if (Tender::withTrashed()->exists()) {
            return;
        }

        $tenders = [
            [
                'number' => 'TND-2026-001',
                'type' => TenderType::Goods,
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Хариди техникаи наҷотдиҳӣ'],
                'ru' => ['title' => 'Закупка спасательной техники'],
                'en' => ['title' => 'Procurement of rescue equipment'],
            ],
            [
                'number' => 'TND-2026-002',
                'type' => TenderType::Services,
                'status' => ContentStatus::Published,
                'tj' => ['title' => 'Хизматрасонии таъмири автомобилҳо'],
                'ru' => ['title' => 'Услуги по ремонту автотранспорта'],
                'en' => ['title' => 'Vehicle maintenance services'],
            ],
            [
                'number' => 'TND-2026-DRAFT',
                'type' => TenderType::Goods,
                'status' => ContentStatus::Draft,
                'tj' => ['title' => '[DRAFT] Тендери санҷишӣ'],
                'ru' => ['title' => '[DRAFT] Тестовый тендер'],
                'en' => ['title' => '[DRAFT] Test tender'],
            ],
        ];

        foreach ($tenders as $i => $data) {
            $tender = Tender::create([
                'tender_number' => $data['number'],
                'status' => $data['status'],
                'type' => $data['type'],
                'budget' => 250000 + ($i * 150000),
                'lots_count' => 1,
                'published_at' => $data['status'] === ContentStatus::Published ? now()->subDay() : null,
                'deadline_at' => now()->addDays(14),
            ]);

            $tender->upsertTranslations($this->trilingual($data, function (string $locale, array $t) use ($data): array {
                return [
                    'title' => $t['title'],
                    'slug' => $this->slugFor($data['en']['title'], $locale),
                    'organizer' => $locale === 'en' ? 'CoES RT' : ($locale === 'ru' ? 'КЧС РТ' : 'КҲФ ҶТ'),
                    'summary' => $t['title'],
                    'description' => '<p>'.$t['title'].'</p>',
                    'requirements' => '<p>'.($locale === 'en' ? 'Licensed suppliers only.' : ($locale === 'ru' ? 'Только лицензированные поставщики.' : 'Танҳо таъминкунандагони иҷозатдошта.')).'</p>',
                    'terms' => '<p>'.($locale === 'en' ? 'See tender documentation.' : ($locale === 'ru' ? 'См. тендерную документацию.' : 'Ҳуҷҷатҳои тендерро бинед.')).'</p>',
                ];
            }));
        }
    }

    /**
     * @param  Collection<string, int>  $regions
     */
    private function seedIncidents(Collection $regions): void
    {
        if (Incident::withTrashed()->exists()) {
            return;
        }

        $incidents = [
            [
                'type' => IncidentType::Mudflow,
                'level' => HazardLevel::Critical,
                'status' => IncidentStatus::Active,
                'region' => 'KHATLON',
                'hours' => 4,
                'tj' => ['title' => 'Фуромадани сел дар ноҳияи Рашт', 'description' => 'Қитъаи роҳ баста шуд. Воҳидҳои наҷотдиҳӣ дар ҷойи ҳодиса кор мекунанд.'],
                'ru' => ['title' => 'Сход селевого потока в районе Рашт', 'description' => 'Перекрыт участок автодороги. Спасательные подразделения работают на месте.'],
                'en' => ['title' => 'Mudflow in the Rasht district', 'description' => 'A road section is blocked. Rescue units are working on site.'],
            ],
            [
                'type' => IncidentType::Earthquake,
                'level' => HazardLevel::Danger,
                'status' => IncidentStatus::Controlled,
                'region' => 'KHATLON',
                'hours' => 14,
                'tj' => ['title' => 'Заминҷунбӣ бо шиддати 4.6', 'description' => 'Талафот ва харобӣ нест.'],
                'ru' => ['title' => 'Землетрясение магнитудой 4.6', 'description' => 'Жертв и разрушений нет.'],
                'en' => ['title' => 'Magnitude 4.6 earthquake', 'description' => 'No casualties or damage.'],
            ],
            [
                'type' => IncidentType::Flood,
                'level' => HazardLevel::Elevated,
                'status' => IncidentStatus::Controlled,
                'region' => 'GBAO',
                'hours' => 26,
                'tj' => ['title' => 'Баланд шудани сатҳи об дар дарёҳои кӯҳӣ', 'description' => 'Омодабоши баланд эълон шуд.'],
                'ru' => ['title' => 'Подъём уровня воды в горных реках', 'description' => 'Объявлена повышенная готовность.'],
                'en' => ['title' => 'Rising water levels in mountain rivers', 'description' => 'Heightened alert declared.'],
            ],
            [
                'type' => IncidentType::Avalanche,
                'level' => HazardLevel::Normal,
                'status' => IncidentStatus::Resolved,
                'region' => 'SUGHD',
                'hours' => 60,
                'tj' => ['title' => 'Фуромадани тарма дар ағбаи Шаҳристон', 'description' => 'Ҳаракати нақлиёт барқарор шуд.'],
                'ru' => ['title' => 'Сход лавины на перевале Шахристан', 'description' => 'Дорожное сообщение восстановлено.'],
                'en' => ['title' => 'Avalanche on the Shahriston pass', 'description' => 'Traffic has been restored.'],
            ],
        ];

        foreach ($incidents as $data) {
            $incident = Incident::create([
                'type' => $data['type'],
                'hazard_level' => $data['level'],
                'status' => $data['status'],
                'region_id' => $regions[$data['region']] ?? null,
                'latitude' => 38.5 + (mt_rand(-200, 200) / 100),
                'longitude' => 70.0 + (mt_rand(-200, 200) / 100),
                'occurred_at' => now()->subHours($data['hours']),
            ]);

            $incident->upsertTranslations($this->trilingual($data, fn (string $locale, array $t): array => [
                'title' => $t['title'],
                'description' => $t['description'],
            ]));
        }
    }

    /**
     * @param  Collection<string, int>  $regions
     */
    private function seedAlerts(Collection $regions): void
    {
        if (Alert::withTrashed()->exists()) {
            return;
        }

        $alert = Alert::create([
            'hazard_level' => HazardLevel::Danger,
            'status' => AlertStatus::Published,
            'region_id' => $regions['KHATLON'] ?? null,
            'is_dismissible' => true,
            'starts_at' => now()->subHours(4),
            'ends_at' => now()->addDays(2),
            'notified_at' => now(),
        ]);

        $alert->upsertTranslations([
            'tj' => ['title' => 'Хатари сел дар водии Рашт', 'body' => 'Ба соҳилҳои дарёҳо наздик нашавед.'],
            'ru' => ['title' => 'Опасность схода селя в Раштской долине', 'body' => 'Не приближайтесь к руслам рек.'],
            'en' => ['title' => 'Mudflow danger in the Rasht valley', 'body' => 'Do not approach riverbeds.'],
        ]);

        $draft = Alert::create([
            'hazard_level' => HazardLevel::Elevated,
            'status' => AlertStatus::Draft,
            'region_id' => null,
            'is_dismissible' => true,
            'starts_at' => null,
            'ends_at' => null,
            'notified_at' => null,
        ]);

        $draft->upsertTranslations([
            'tj' => ['title' => '[DRAFT] Огоҳиномаи санҷишӣ', 'body' => 'Барои санҷиши confirm-диалог.'],
            'ru' => ['title' => '[DRAFT] Тестовое оповещение', 'body' => 'Для проверки confirm-диалога.'],
            'en' => ['title' => '[DRAFT] Test alert', 'body' => 'For testing the publish confirm dialog.'],
        ]);
    }

    /**
     * @param  Collection<string, int>  $regions
     */
    private function seedInbox(Collection $regions): void
    {
        if (Appeal::query()->doesntExist()) {
            Appeal::factory()->count(5)->create(['status' => AppealStatus::New]);
            Appeal::factory()->count(2)->create(['status' => AppealStatus::Answered]);
        }

        if (TouristGroup::query()->doesntExist()) {
            TouristGroup::factory()->count(3)->create(['region_id' => $regions['GBAO'] ?? null]);
        }

        if (Subscriber::query()->doesntExist()) {
            Subscriber::factory()->count(8)->create([
                'status' => SubscriptionStatus::Confirmed,
                'confirmed_at' => now(),
                'topics' => [SubscriptionTopic::Alerts->value, SubscriptionTopic::News->value],
            ]);
            Subscriber::factory()->count(3)->create();
        }
    }
}
