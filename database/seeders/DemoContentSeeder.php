<?php

namespace Database\Seeders;

use App\Enums\AlertStatus;
use App\Enums\AppealStatus;
use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Enums\GuideAudience;
use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\PostType;
use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTopic;
use App\Models\Alert;
use App\Models\Appeal;
use App\Models\Category;
use App\Models\Document;
use App\Models\Guide;
use App\Models\Incident;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\Region;
use App\Models\Subscriber;
use App\Models\TouristGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Demo content for local/staging so the portal and CMS are not empty (ТЗ §6). Trilingual
 * (tj/ru/en). Each section is guarded so re-running the seeder does not duplicate rows.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('email', 'aminjon1065@gmail.com')->first();
        $regions = Region::query()->pluck('id', 'code');

        $categories = $this->seedCategories();
        $this->seedPosts($author?->id, $categories);
        $this->seedPages();
        $this->seedIncidents($regions);
        $this->seedAlert($regions);
        $this->seedDocuments();
        $this->seedGuides();
        $this->seedInbox($regions);
    }

    private function seedGuides(): void
    {
        if (Guide::query()->exists()) {
            return;
        }

        $guides = [
            [
                'hazard' => IncidentType::Earthquake, 'audience' => GuideAudience::General,
                'tj' => ['title' => 'Ҳангоми заминҷунбӣ чӣ бояд кард', 'summary' => 'Қоидаҳои рафтор пеш аз, ҳангом ва пас аз ларзиш.', 'content' => '<h2>Пеш аз заминҷунбӣ</h2><ul><li>Ашёи вазнинро маҳкам кунед.</li><li>Маҷмӯи фавриро омода созед.</li></ul><h2>Ҳангоми ларзиш</h2><ul><li>Оромиро нигоҳ доред.</li><li>Зери миз пинҳон шавед ва сарро ҳифз кунед.</li></ul>'],
                'ru' => ['title' => 'Как действовать при землетрясении', 'summary' => 'Правила поведения до, во время и после толчков.', 'content' => '<h2>До землетрясения</h2><ul><li>Закрепите тяжёлые предметы.</li><li>Подготовьте тревожный чемоданчик.</li></ul><h2>Во время толчков</h2><ul><li>Сохраняйте спокойствие.</li><li>Укройтесь под столом и защитите голову.</li></ul>'],
                'en' => ['title' => 'What to do during an earthquake', 'summary' => 'Rules of conduct before, during and after tremors.', 'content' => '<h2>Before</h2><ul><li>Secure heavy objects.</li><li>Prepare an emergency kit.</li></ul><h2>During</h2><ul><li>Stay calm.</li><li>Take cover under a table and protect your head.</li></ul>'],
            ],
            [
                'hazard' => IncidentType::Flood, 'audience' => GuideAudience::General,
                'tj' => ['title' => 'Ҳангоми обхезӣ ва сел', 'summary' => 'Чӣ тавр аз минтақаи хатарнок дур шудан мумкин аст.', 'content' => '<ul><li>Ба ҷойҳои баланд гузаред.</li><li>Ба соҳилҳои дарёҳо наздик нашавед.</li><li>Дастури наҷотдиҳандагонро иҷро кунед.</li></ul>'],
                'ru' => ['title' => 'При наводнении и селе', 'summary' => 'Как уйти из опасной зоны.', 'content' => '<ul><li>Поднимитесь на возвышенность.</li><li>Не приближайтесь к руслам рек.</li><li>Следуйте указаниям спасателей.</li></ul>'],
                'en' => ['title' => 'During a flood or mudflow', 'summary' => 'How to leave a danger zone.', 'content' => '<ul><li>Move to higher ground.</li><li>Stay away from riverbeds.</li><li>Follow rescuers’ instructions.</li></ul>'],
            ],
            [
                'hazard' => IncidentType::Fire, 'audience' => GuideAudience::General,
                'tj' => ['title' => 'Ҳангоми сӯхтор', 'summary' => 'Амалҳои аввалия ҳангоми оташсӯзӣ.', 'content' => '<ul><li>Ба рақами 101 занг занед.</li><li>Бинокориро зуд тарк кунед.</li><li>Бо дастмоли тар нафасро ҳифз кунед.</li></ul>'],
                'ru' => ['title' => 'При пожаре', 'summary' => 'Первоочередные действия при возгорании.', 'content' => '<ul><li>Позвоните по номеру 101.</li><li>Быстро покиньте здание.</li><li>Защитите дыхание влажной тканью.</li></ul>'],
                'en' => ['title' => 'In case of fire', 'summary' => 'First actions in a fire.', 'content' => '<ul><li>Call 101.</li><li>Leave the building quickly.</li><li>Cover your breathing with a wet cloth.</li></ul>'],
            ],
            [
                'hazard' => IncidentType::Earthquake, 'audience' => GuideAudience::Children,
                'tj' => ['title' => 'Заминҷунбӣ: барои хонандагон', 'summary' => 'Дастур барои мактаббачагон.', 'content' => '<ul><li>Натарс, ором бош.</li><li>Зери парта пинҳон шав.</li><li>Пас аз ларзиш бо муаллим бадар бар о.</li></ul>'],
                'ru' => ['title' => 'Землетрясение: для школьников', 'summary' => 'Памятка для детей.', 'content' => '<ul><li>Не бойся, сохраняй спокойствие.</li><li>Спрячься под партой.</li><li>После толчков выходи вместе с учителем.</li></ul>'],
                'en' => ['title' => 'Earthquake: for pupils', 'summary' => 'A memo for children.', 'content' => '<ul><li>Don’t panic, stay calm.</li><li>Hide under your desk.</li><li>After the tremors, leave with your teacher.</li></ul>'],
            ],
        ];

        foreach ($guides as $i => $data) {
            $guide = Guide::create([
                'hazard_type' => $data['hazard'],
                'audience' => $data['audience'],
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
            ]);

            $guide->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'slug' => Str::slug($data['en']['title']).'-'.$locale,
                    'summary' => $data[$locale]['summary'],
                    'content' => $data[$locale]['content'],
                ],
            ])->all());
        }
    }

    /**
     * @return array<string, int> map of slug-key → category id
     */
    private function seedCategories(): array
    {
        $defs = [
            'civil-defense' => ['tj' => 'Мудофиаи гражданӣ', 'ru' => 'Гражданская оборона', 'en' => 'Civil defense'],
            'prevention' => ['tj' => 'Пешгирии ҲФ', 'ru' => 'Профилактика ЧС', 'en' => 'Emergency prevention'],
            'cooperation' => ['tj' => 'Ҳамкории байналмилалӣ', 'ru' => 'Международное сотрудничество', 'en' => 'International cooperation'],
        ];

        $map = [];

        foreach ($defs as $key => $names) {
            $category = Category::firstOrCreate(['sort_order' => array_search($key, array_keys($defs), true) + 1]);

            if (! $category->wasRecentlyCreated && $category->translations()->exists()) {
                $map[$key] = $category->id;

                continue;
            }

            $category->upsertTranslations(collect($names)->map(fn (string $name): array => [
                'name' => $name,
                'slug' => Str::slug($name).'-'.$key,
            ])->all());

            $map[$key] = $category->id;
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $categories
     */
    private function seedPosts(?int $authorId, array $categories): void
    {
        if (Post::query()->exists()) {
            return;
        }

        $posts = [
            [
                'type' => PostType::PressRelease, 'category' => 'civil-defense', 'days' => 0,
                'tj' => ['title' => 'Дар Душанбе машқҳои мудофиаи гражданӣ гузаронида шуданд', 'excerpt' => 'Амалҳои аҳолӣ ҳангоми сигнали «Диққат ба ҳама!» ва эвакуатсия аз бино машқ карда шуданд.'],
                'ru' => ['title' => 'Учения по гражданской обороне прошли в Душанбе', 'excerpt' => 'Отработаны действия населения при сигнале «Внимание всем!» и эвакуация из административных зданий.'],
                'en' => ['title' => 'Civil-defense drills held in Dushanbe', 'excerpt' => 'Public response to the “Attention all!” signal and evacuation from administrative buildings were practised.'],
            ],
            [
                'type' => PostType::Summary, 'category' => 'prevention', 'days' => 1,
                'tj' => ['title' => 'Сатҳи оби дарёи Вахш ба эътидол омад', 'excerpt' => 'Постҳои гидрологӣ паст шудани сатҳро қайд мекунанд. Хатари обхезии соҳилҳо бартараф шуд.'],
                'ru' => ['title' => 'Уровень воды в реке Вахш стабилизировался', 'excerpt' => 'Гидропосты фиксируют снижение уровня. Угроза подтопления прибрежных участков снята.'],
                'en' => ['title' => 'Water level on the Vakhsh river has stabilised', 'excerpt' => 'Gauges record a falling level. The risk of flooding along the banks has been lifted.'],
            ],
            [
                'type' => PostType::Announcement, 'category' => 'prevention', 'days' => 3,
                'tj' => ['title' => 'Дастурамал: ҳангоми заминҷунбӣ чӣ бояд кард', 'excerpt' => 'Кумита қоидаҳои рафторро пеш аз, ҳангом ва пас аз ларзишҳои зеризаминӣ ёдрас мекунад.'],
                'ru' => ['title' => 'Памятка: как действовать при землетрясении', 'excerpt' => 'Комитет напоминает правила поведения до, во время и после подземных толчков.'],
                'en' => ['title' => 'Guide: what to do during an earthquake', 'excerpt' => 'The Committee recalls the rules of conduct before, during and after tremors.'],
            ],
            [
                'type' => PostType::News, 'category' => 'civil-defense', 'days' => 5,
                'tj' => ['title' => 'Дар водии Рашт пости наҷотдиҳии нав кушода шуд', 'excerpt' => 'Пост бо техникаи корҳои наҷотдиҳии кӯҳӣ ва навбатдории шабонарӯзӣ муҷаҳҳаз аст.'],
                'ru' => ['title' => 'Открыт новый спасательный пост в Раштской долине', 'excerpt' => 'Пост оснащён техникой для горноспасательных работ и круглосуточным дежурством.'],
                'en' => ['title' => 'A new rescue post opened in the Rasht valley', 'excerpt' => 'The post is equipped for mountain rescue work and round-the-clock duty.'],
            ],
            [
                'type' => PostType::PressRelease, 'category' => 'cooperation', 'days' => 7,
                'tj' => ['title' => 'Созишнома оид ба мониторинги фаромарзии обхезиҳо имзо шуд', 'excerpt' => 'Ҳуҷҷат мубодилаи додаҳои хадамоти гидрометеорологиро дар минтақа тақвият медиҳад.'],
                'ru' => ['title' => 'Подписано соглашение о трансграничном мониторинге паводков', 'excerpt' => 'Документ усиливает обмен данными гидрометеослужб между странами региона.'],
                'en' => ['title' => 'Agreement on transboundary flood monitoring signed', 'excerpt' => 'The document strengthens hydrometeorological data exchange across the region.'],
            ],
            [
                'type' => PostType::Announcement, 'category' => 'prevention', 'days' => 9,
                'tj' => ['title' => 'Маъракаи омодагӣ ба мавсими сел оғоз ёфт', 'excerpt' => 'Санҷиши системаҳои огоҳонӣ ва тоза кардани маҷрои дарёҳо дар минтақаҳои хатарнок.'],
                'ru' => ['title' => 'Стартовала кампания по подготовке к селевому сезону', 'excerpt' => 'Проверка систем оповещения и расчистка русел в зонах повышенного риска.'],
                'en' => ['title' => 'Mudflow-season readiness campaign has started', 'excerpt' => 'Alert systems are being tested and riverbeds cleared in high-risk zones.'],
            ],
        ];

        foreach ($posts as $data) {
            $post = Post::create([
                'type' => $data['type'],
                'category_id' => $categories[$data['category']] ?? null,
                'author_id' => $authorId,
                'status' => ContentStatus::Published,
                'published_at' => now()->subDays($data['days']),
            ]);

            $post->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'slug' => Str::slug($data['en']['title']).'-'.$locale,
                    'excerpt' => $data[$locale]['excerpt'],
                    'body' => '<p>'.$data[$locale]['excerpt'].'</p><p>'.$data[$locale]['excerpt'].'</p>',
                ],
            ])->all());
        }
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'tj' => ['title' => 'Дар бораи Кумита', 'body' => 'Кумитаи ҳолатҳои фавқулодда ва мудофиаи гражданӣ мақоми ваколатдори давлатӣ дар соҳаи ҳифзи аҳолӣ ва ҳудуд мебошад.'],
                'ru' => ['title' => 'О Комитете', 'body' => 'Комитет по чрезвычайным ситуациям и гражданской обороне — уполномоченный государственный орган в сфере защиты населения и территорий.'],
                'en' => ['title' => 'About the Committee', 'body' => 'The Committee for Emergency Situations and Civil Defense is the authorised state body for the protection of the population and territory.'],
            ],
            [
                'tj' => ['title' => 'Фаъолият', 'body' => 'Самтҳои асосии фаъолият: пешгирӣ ва бартарафсозии ҳолатҳои фавқулодда, мудофиаи гражданӣ, корҳои наҷотдиҳӣ ва огоҳонии аҳолӣ.'],
                'ru' => ['title' => 'Деятельность', 'body' => 'Основные направления деятельности: предупреждение и ликвидация чрезвычайных ситуаций, гражданская оборона, спасательные работы и оповещение населения.'],
                'en' => ['title' => 'Activities', 'body' => 'Core activities: prevention and response to emergencies, civil defense, rescue operations, and public warning.'],
            ],
            [
                'tj' => ['title' => 'Тамос', 'body' => 'Телефони ягонаи боварӣ: 112. Душанбе, Ҷумҳурии Тоҷикистон.'],
                'ru' => ['title' => 'Контакты', 'body' => 'Единый телефон доверия: 112. г. Душанбе, Республика Таджикистан.'],
                'en' => ['title' => 'Contacts', 'body' => 'Unified helpline: 112. Dushanbe, Republic of Tajikistan.'],
            ],
        ];

        foreach ($pages as $i => $data) {
            $slug = Str::slug($data['en']['title']);

            // Idempotent per page: skip if a translation with this English slug already exists.
            if (PageTranslation::query()->where('slug', $slug.'-en')->exists()) {
                continue;
            }

            $page = Page::create(['parent_id' => null, 'status' => ContentStatus::Published, 'sort_order' => $i + 1]);

            $page->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'slug' => $slug.'-'.$locale,
                    'content' => '<p>'.$data[$locale]['body'].'</p>',
                ],
            ])->all());
        }
    }

    /**
     * @param  Collection<string, int>  $regions
     */
    private function seedIncidents($regions): void
    {
        if (Incident::query()->exists()) {
            return;
        }

        $incidents = [
            [
                'type' => IncidentType::Mudflow, 'level' => HazardLevel::Critical, 'status' => IncidentStatus::Active, 'region' => 'KHATLON', 'hours' => 4,
                'tj' => ['title' => 'Фуромадани сел дар ноҳияи Рашт', 'description' => 'Қитъаи роҳи автомобилгард баста шуд. Воҳидҳои наҷотдиҳӣ дар ҷойи ҳодиса кор мекунанд.'],
                'ru' => ['title' => 'Сход селевого потока в районе Рашт', 'description' => 'Перекрыт участок автодороги. Спасательные подразделения работают на месте, ведётся расчистка.'],
                'en' => ['title' => 'Mudflow in the Rasht district', 'description' => 'A road section is blocked. Rescue units are working on site and clearing the area.'],
            ],
            [
                'type' => IncidentType::Earthquake, 'level' => HazardLevel::Danger, 'status' => IncidentStatus::Controlled, 'region' => 'KHATLON', 'hours' => 14,
                'tj' => ['title' => 'Заминҷунбӣ бо шиддати 4.6', 'description' => 'Талафот ва харобӣ нест. Объектҳои иҷтимоӣ муоина карда мешаванд.'],
                'ru' => ['title' => 'Землетрясение магнитудой 4.6', 'description' => 'Жертв и разрушений нет. Проводится обследование социальных объектов.'],
                'en' => ['title' => 'Magnitude 4.6 earthquake', 'description' => 'No casualties or damage. Social facilities are being inspected.'],
            ],
            [
                'type' => IncidentType::Flood, 'level' => HazardLevel::Elevated, 'status' => IncidentStatus::Controlled, 'region' => 'GBAO', 'hours' => 26,
                'tj' => ['title' => 'Баланд шудани сатҳи об дар дарёҳои кӯҳӣ', 'description' => 'Омодабоши баланд эълон шуд. Ба аҳолӣ тавсия дода мешавад, ки аз минтақаҳои наздисоҳилӣ дурӣ ҷӯянд.'],
                'ru' => ['title' => 'Подъём уровня воды в горных реках', 'description' => 'Объявлена повышенная готовность. Населению рекомендовано избегать прибрежных зон.'],
                'en' => ['title' => 'Rising water levels in mountain rivers', 'description' => 'Heightened alert declared. Residents are advised to avoid riverside areas.'],
            ],
            [
                'type' => IncidentType::Avalanche, 'level' => HazardLevel::Normal, 'status' => IncidentStatus::Resolved, 'region' => 'SUGHD', 'hours' => 60,
                'tj' => ['title' => 'Фуромадани тарма дар ағбаи Шаҳристон', 'description' => 'Ҳаракати нақлиёт барқарор шуд. Зарардида нест.'],
                'ru' => ['title' => 'Сход лавины на перевале Шахристан', 'description' => 'Дорожное сообщение восстановлено. Пострадавших нет.'],
                'en' => ['title' => 'Avalanche on the Shahriston pass', 'description' => 'Traffic has been restored. No injuries.'],
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

            $incident->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => ['title' => $data[$locale]['title'], 'description' => $data[$locale]['description']],
            ])->all());
        }
    }

    /**
     * @param  Collection<string, int>  $regions
     */
    private function seedAlert($regions): void
    {
        if (Alert::query()->exists()) {
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
            'tj' => ['title' => 'Хатари сел дар водии Рашт', 'body' => 'Ба соҳилҳои дарёҳо наздик нашавед. Дастури наҷотдиҳандагонро иҷро кунед.'],
            'ru' => ['title' => 'Опасность схода селя в Раштской долине', 'body' => 'Не приближайтесь к руслам рек. Следуйте указаниям спасателей.'],
            'en' => ['title' => 'Mudflow danger in the Rasht valley', 'body' => 'Do not approach riverbeds. Follow the instructions of rescuers.'],
        ]);
    }

    private function seedDocuments(): void
    {
        if (Document::query()->exists()) {
            return;
        }

        $docs = [
            [
                'type' => DocumentType::Law, 'days' => 30,
                'tj' => ['name' => 'Қонуни ҶТ «Дар бораи ҳифзи аҳолӣ ва ҳудуд аз ҳолатҳои фавқулодда»'],
                'ru' => ['name' => 'Закон РТ «О защите населения и территорий от чрезвычайных ситуаций»'],
                'en' => ['name' => 'Law of the RT “On protection of population and territories from emergencies”'],
            ],
            [
                'type' => DocumentType::Plan, 'days' => 44,
                'tj' => ['name' => 'Нақшаи чорабиниҳои асосии мудофиаи гражданӣ барои соли 2026'],
                'ru' => ['name' => 'План основных мероприятий в области гражданской обороны на 2026 год'],
                'en' => ['name' => 'Plan of principal civil-defense measures for 2026'],
            ],
            [
                'type' => DocumentType::Form, 'days' => 56,
                'tj' => ['name' => 'Шакли огоҳинома дар бораи баромадани гурӯҳи сайёҳӣ ба хатсайр'],
                'ru' => ['name' => 'Форма уведомления о выходе туристской группы на маршрут'],
                'en' => ['name' => 'Tourist-group route-departure notification form'],
            ],
            [
                'type' => DocumentType::Report, 'days' => 70,
                'tj' => ['name' => 'Ҳисобот оид ба фаъолияти назоратӣ барои семоҳаи I соли 2026'],
                'ru' => ['name' => 'Отчёт о результатах надзорной деятельности за I квартал 2026 года'],
                'en' => ['name' => 'Report on supervisory activity for Q1 2026'],
            ],
        ];

        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

        foreach ($docs as $data) {
            $document = Document::create([
                'type' => $data['type'],
                'source' => 'КЧС',
                'document_date' => now()->subDays($data['days']),
                'status' => ContentStatus::Published,
                'sort_order' => 0,
            ]);

            $document->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => ['name' => $data[$locale]['name'], 'description' => null],
            ])->all());

            $document->addMediaFromString($pdf)
                ->usingFileName(Str::slug($data['en']['name']).'.pdf')
                ->toMediaCollection(Document::FILES_COLLECTION);
        }
    }

    /**
     * @param  Collection<string, int>  $regions
     */
    private function seedInbox($regions): void
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
            Subscriber::factory()->count(3)->create(); // pending
        }
    }
}
