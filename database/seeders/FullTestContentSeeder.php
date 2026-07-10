<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\EmploymentType;
use App\Enums\PollType;
use App\Enums\ServiceCategory;
use App\Enums\TenderType;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GovService;
use App\Models\Leader;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Post;
use App\Models\Statistic;
use App\Models\Subdivision;
use App\Models\Tag;
use App\Models\Tender;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * Extended demo dataset for local/staging full-site testing. Complements {@see DemoContentSeeder}
 * with leadership, structure, galleries, services, HR, polls, tags, and media covers.
 * Idempotent — safe to re-run; skips sections that are already populated.
 */
class FullTestContentSeeder extends Seeder
{
    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function run(): void
    {
        $this->seedPrivacyPages();
        $this->seedLeaders();
        $this->seedSubdivisions();
        $this->seedGallery();
        $this->seedFaq();
        $this->seedPolls();
        $this->seedVacancies();
        $this->seedTenders();
        $this->seedGovServices();
        $this->seedStatistics();
        $this->seedTags();
        $this->seedPostMedia();
    }

    private function seedPrivacyPages(): void
    {
        if (PageTranslation::query()->where('slug', 'privacy-policy-tj')->exists()) {
            return;
        }

        $page = Page::create([
            'parent_id' => null,
            'status' => ContentStatus::Published,
            'sort_order' => 90,
        ]);

        $page->upsertTranslations([
            'tj' => [
                'title' => 'Сиёсати махфият',
                'slug' => 'privacy-policy-tj',
                'content' => '<p>Ин саҳифа барои санҷиши портал истифода мешавад. Маълумоти шахсӣ танҳо мувофиқи қонунгузории ҶТ коркард мегардад.</p>',
            ],
            'ru' => [
                'title' => 'Политика конфиденциальности',
                'slug' => 'privacy-policy-ru',
                'content' => '<p>Эта страница используется для тестирования портала. Персональные данные обрабатываются в соответствии с законодательством РТ.</p>',
            ],
            'en' => [
                'title' => 'Privacy policy',
                'slug' => 'privacy-policy-en',
                'content' => '<p>This page is used for portal testing. Personal data is processed in accordance with the legislation of the RT.</p>',
            ],
        ]);
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
                'tj' => ['full_name' => 'Қодиров Фаррух Абдулло', 'position' => 'Раиси Кумита', 'bio' => 'Роҳбари Кумитаи ҳолатҳои фавқулодда ва мудофиаи гражданӣ.'],
                'ru' => ['full_name' => 'Кодиров Фаррух Абдулло', 'position' => 'Председатель Комитета', 'bio' => 'Руководитель Комитета по чрезвычайным ситуациям и гражданской обороне.'],
                'en' => ['full_name' => 'Kodirov Farrux Abdullo', 'position' => 'Chairman of the Committee', 'bio' => 'Head of the Committee for Emergency Situations and Civil Defense.'],
            ],
            [
                'email' => 'deputy@khf.tj',
                'phone' => '+992 37 221-00-02',
                'tj' => ['full_name' => 'Раҳимова Гулнора Саид', 'position' => 'Ҷонишини раис', 'bio' => 'Масъули ҳамоҳангсозии амалияҳои наҷотдиҳӣ ва омодагии фавқулодда.'],
                'ru' => ['full_name' => 'Рахимова Гулнора Саид', 'position' => 'Заместитель председателя', 'bio' => 'Курирует координацию спасательных операций и готовность к ЧС.'],
                'en' => ['full_name' => 'Rahimova Gulnora Said', 'position' => 'Deputy Chairman', 'bio' => 'Oversees coordination of rescue operations and emergency readiness.'],
            ],
            [
                'email' => 'press@khf.tj',
                'phone' => '+992 37 221-00-03',
                'tj' => ['full_name' => 'Назаров Фирӯз Ҷӯра', 'position' => 'Мушовири раис', 'bio' => 'Масъули робита бо омма ва матбуот.'],
                'ru' => ['full_name' => 'Назаров Фируз Джура', 'position' => 'Советник председателя', 'bio' => 'Отвечает за связи с общественностью и пресс-службу.'],
                'en' => ['full_name' => 'Nazarov Firuz Jura', 'position' => 'Advisor to the Chairman', 'bio' => 'Responsible for public relations and the press office.'],
            ],
        ];

        foreach ($leaders as $i => $data) {
            $leader = Leader::create([
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            $leader->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'full_name' => $data[$locale]['full_name'],
                    'position' => $data[$locale]['position'],
                    'bio' => $data[$locale]['bio'],
                    'reception' => $locale === 'en'
                        ? 'Mon, Wed: 9:00–12:00'
                        : ($locale === 'ru' ? 'Пн, Ср: 9:00–12:00' : 'Дш, Чш: 9:00–12:00'),
                ],
            ])->all());

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
                'tj' => ['name' => 'Идораи корҳои наҷотдиҳӣ', 'head' => 'Раҳимова Г.С.', 'functions' => 'Ташкили амалияҳои наҷот ва кумакрасонӣ.', 'address' => 'ш. Душанбе'],
                'ru' => ['name' => 'Управление спасательных работ', 'head' => 'Рахимова Г.С.', 'functions' => 'Организация спасательных и аварийно-спасательных работ.', 'address' => 'г. Душанбе'],
                'en' => ['name' => 'Rescue operations department', 'head' => 'Rahimova G.S.', 'functions' => 'Organisation of rescue and emergency response.', 'address' => 'Dushanbe'],
            ],
            [
                'tj' => ['name' => 'Маркази матбуот', 'head' => 'Сафарова Н.А.', 'functions' => 'Хабарҳо, матбуот ва робита бо омма.', 'address' => 'ш. Душанбе'],
                'ru' => ['name' => 'Пресс-центр', 'head' => 'Сафарова Н.А.', 'functions' => 'Новости, пресс-служба и связи с общественностью.', 'address' => 'г. Душанбе'],
                'en' => ['name' => 'Press centre', 'head' => 'Safarova N.A.', 'functions' => 'News, press service and public relations.', 'address' => 'Dushanbe'],
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

    private function seedGallery(): void
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

            $gallery->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'slug' => Str::slug($data['en']['title']).'-'.$locale,
                    'description' => $data[$locale]['description'],
                ],
            ])->all());

            foreach (['emblem-tj.webp', 'president.webp'] as $file) {
                $this->attachPublicImage($gallery, Gallery::PHOTOS_COLLECTION, $file, preserveOriginal: true);
            }
        }
    }

    private function seedFaq(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $items = [
            [
                'tj' => ['question' => 'Ба кадом рақам занг занам дар ҳолати фавқулодда?', 'answer' => 'Ба рақами ягонаи боварии 112 занг занед. Ин хизмат ройгон ва шабонарӯзӣ фаъол аст.'],
                'ru' => ['question' => 'По какому номеру звонить в чрезвычайной ситуации?', 'answer' => 'Звоните по единому номеру доверия 112. Услуга бесплатна и работает круглосуточно.'],
                'en' => ['question' => 'Which number should I call in an emergency?', 'answer' => 'Call the unified helpline 112. The service is free and operates around the clock.'],
            ],
            [
                'tj' => ['question' => 'Чӣ тавр гурӯҳи сайёҳиро ба қайд гирам?', 'answer' => 'Дар бахши «Сайёҳӣ» дар сомона шакли бақайдгирӣро пур кунед ё ба идораи минтақавии Кумита муроҷиат кунед.'],
                'ru' => ['question' => 'Как зарегистрировать туристическую группу?', 'answer' => 'Заполните форму в разделе «Туризм» на сайте или обратитесь в региональное управление Комитета.'],
                'en' => ['question' => 'How do I register a tourist group?', 'answer' => 'Fill in the form in the Tourism section on the site or contact the regional office of the Committee.'],
            ],
            [
                'tj' => ['question' => 'Огоҳиномаҳо чӣ гуна расонида мешаванд?', 'answer' => 'Шумо метавонед ба хабарномаи почтаи электронӣ ё огоҳиномаҳои браузерӣ обуна шавед.'],
                'ru' => ['question' => 'Как доставляются оповещения?', 'answer' => 'Вы можете подписаться на e-mail рассылку или браузерные уведомления.'],
                'en' => ['question' => 'How are alerts delivered?', 'answer' => 'You can subscribe to email newsletters or browser notifications.'],
            ],
        ];

        foreach ($items as $i => $data) {
            $faq = Faq::create([
                'status' => ContentStatus::Published,
                'sort_order' => $i + 1,
            ]);

            $faq->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'question' => $data[$locale]['question'],
                    'answer' => '<p>'.$data[$locale]['answer'].'</p>',
                ],
            ])->all());
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
                'tj' => ['title' => 'Сомонаи расмии Кумита чӣ қадар муфид аст?', 'description' => 'Баҳо диҳед, ки маълумоти дар сомона чӣ қадар ба шумо кӯмак мекунад.'],
                'ru' => ['title' => 'Насколько полезен официальный сайт Комитета?', 'description' => 'Оцените, насколько информация на сайте помогает вам.'],
                'en' => ['title' => 'How useful is the Committee’s official website?', 'description' => 'Rate how helpful the information on the site is for you.'],
            ],
            [
                'type' => PollType::AntiCorruptionExpertise,
                'options' => [
                    'tj' => ['Пурра', 'Қисман', 'Нақшан не'],
                    'ru' => ['Полностью', 'Частично', 'Практически нет'],
                    'en' => ['Fully', 'Partially', 'Hardly at all'],
                ],
                'tj' => ['title' => 'Огоҳиномаҳои оид ба фасод', 'description' => 'Оё шумо дар бораи ҳуқуқҳои шаҳрвандон оид ба фасод огоҳ ҳастед?'],
                'ru' => ['title' => 'Антикоррупционная грамотность', 'description' => 'Насколько вы осведомлены о правах граждан в сфере противодействия коррупции?'],
                'en' => ['title' => 'Anti-corruption awareness', 'description' => 'How informed are you about citizens’ rights in anti-corruption matters?'],
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

            $poll->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'description' => $data[$locale]['description'],
                    'slug' => Str::slug($data['en']['title']).'-'.$locale,
                ],
            ])->all());

            foreach ($data['options']['tj'] as $optionIndex => $tjLabel) {
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

    private function seedVacancies(): void
    {
        if (Vacancy::query()->exists()) {
            return;
        }

        $vacancies = [
            [
                'tj' => ['title' => 'Мутахассиси IT', 'department' => 'Идораи технология', 'location' => 'Душанбе'],
                'ru' => ['title' => 'IT-специалист', 'department' => 'Управление технологий', 'location' => 'Душанбе'],
                'en' => ['title' => 'IT specialist', 'department' => 'Technology department', 'location' => 'Dushanbe'],
            ],
            [
                'tj' => ['title' => 'Корманди наҷотдиҳӣ', 'department' => 'Идораи наҷот', 'location' => 'Хатлон'],
                'ru' => ['title' => 'Спасатель', 'department' => 'Управление спасения', 'location' => 'Хатлон'],
                'en' => ['title' => 'Rescue worker', 'department' => 'Rescue department', 'location' => 'Khatlon'],
            ],
        ];

        foreach ($vacancies as $i => $data) {
            $vacancy = Vacancy::create([
                'status' => ContentStatus::Published,
                'employment_type' => EmploymentType::FullTime,
                'positions_count' => 1,
                'published_at' => now()->subDays(2),
                'deadline_at' => now()->addDays(21),
            ]);

            $vacancy->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'slug' => Str::slug($data['en']['title']).'-'.$locale,
                    'department' => $data[$locale]['department'],
                    'location' => $data[$locale]['location'],
                    'summary' => $data[$locale]['title'],
                    'description' => '<p>'.$data[$locale]['title'].'</p>',
                    'requirements' => '<ul><li>'.($locale === 'en' ? 'Relevant experience' : ($locale === 'ru' ? 'Соответствующий опыт' : 'Таҷрибаи мувофиқ')).'</li></ul>',
                    'responsibilities' => '<ul><li>'.($locale === 'en' ? 'Daily duties' : ($locale === 'ru' ? 'Ежедневные обязанности' : 'Вазифаҳои ҳаррӯза')).'</li></ul>',
                ],
            ])->all());
        }
    }

    private function seedTenders(): void
    {
        if (Tender::query()->exists()) {
            return;
        }

        $tenders = [
            [
                'number' => 'TND-2026-001',
                'type' => TenderType::Goods,
                'tj' => ['title' => 'Хариди техникаи наҷотдиҳӣ'],
                'ru' => ['title' => 'Закупка спасательной техники'],
                'en' => ['title' => 'Procurement of rescue equipment'],
            ],
            [
                'number' => 'TND-2026-002',
                'type' => TenderType::Services,
                'tj' => ['title' => 'Хизматрасонии таъмири автомобилҳо'],
                'ru' => ['title' => 'Услуги по ремонту автотранспорта'],
                'en' => ['title' => 'Vehicle maintenance services'],
            ],
        ];

        foreach ($tenders as $i => $data) {
            $tender = Tender::create([
                'tender_number' => $data['number'],
                'status' => ContentStatus::Published,
                'type' => $data['type'],
                'budget' => 250000 + ($i * 150000),
                'lots_count' => 1,
                'published_at' => now()->subDay(),
                'deadline_at' => now()->addDays(14),
            ]);

            $tender->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'slug' => Str::slug($data['en']['title']).'-'.$locale,
                    'organizer' => $locale === 'en' ? 'CoES RT' : ($locale === 'ru' ? 'КЧС РТ' : 'КҲФ ҶТ'),
                    'summary' => $data[$locale]['title'],
                    'description' => '<p>'.$data[$locale]['title'].'</p>',
                    'requirements' => '<p>'.($locale === 'en' ? 'Licensed suppliers only.' : ($locale === 'ru' ? 'Только лицензированные поставщики.' : 'Танҳо таъминкунандагони иҷозатдошта.')).'</p>',
                    'terms' => '<p>'.($locale === 'en' ? 'See tender documentation.' : ($locale === 'ru' ? 'См. тендерную документацию.' : 'Ҳуҷҷатҳои тендерро бинед.')).'</p>',
                ],
            ])->all());
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
                'processing_time' => '3–5 '.($i === 2 ? 'days' : 'working days'),
                'fee' => 'Free',
                'sort_order' => $i + 1,
            ]);

            $service->upsertTranslations(collect(['tj', 'ru', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'title' => $data[$locale]['title'],
                    'slug' => Str::slug($data['en']['title']).'-'.$locale,
                    'summary' => $data[$locale]['title'],
                    'description' => '<p>'.$data[$locale]['title'].'</p>',
                    'eligibility' => $locale === 'en' ? 'Citizens and organisations.' : ($locale === 'ru' ? 'Граждане и организации.' : 'Шаҳрвандон ва ташкилотҳо.'),
                    'required_documents' => $locale === 'en' ? 'Application form and ID.' : ($locale === 'ru' ? 'Заявление и удостоверение личности.' : 'Ариза ва шиноснома.'),
                ],
            ])->all());
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

    private function seedTags(): void
    {
        if (Tag::query()->exists()) {
            return;
        }

        $tags = [
            ['key' => 'alert', 'tj' => 'Огоҳинома', 'ru' => 'Оповещение', 'en' => 'Alert'],
            ['key' => 'drill', 'tj' => 'Машқ', 'ru' => 'Учения', 'en' => 'Drill'],
            ['key' => 'rescue', 'tj' => 'Наҷот', 'ru' => 'Спасение', 'en' => 'Rescue'],
        ];

        $tagIds = [];

        foreach ($tags as $data) {
            $tag = Tag::create();
            $tag->upsertTranslations([
                'tj' => ['name' => $data['tj'], 'slug' => $data['key'].'-tj'],
                'ru' => ['name' => $data['ru'], 'slug' => $data['key'].'-ru'],
                'en' => ['name' => $data['en'], 'slug' => $data['key'].'-en'],
            ]);
            $tagIds[] = $tag->id;
        }

        Post::query()->published()->limit(3)->get()->each(function (Post $post, int $index) use ($tagIds): void {
            $post->tags()->syncWithoutDetaching([$tagIds[$index % count($tagIds)]]);
        });
    }

    private function seedPostMedia(): void
    {
        Post::query()
            ->published()
            ->with('media')
            ->get()
            ->each(function (Post $post): void {
                if ($post->getFirstMedia(Post::COVER_COLLECTION) !== null) {
                    return;
                }

                $this->attachPublicImage($post, Post::COVER_COLLECTION, 'emblem-tj.webp');
            });
    }

    /**
     * @param  HasMedia&Model  $model
     */
    private function attachPublicImage(
        $model,
        string $collection,
        string $filename,
        bool $preserveOriginal = false,
    ): void {
        $path = public_path('images/'.$filename);

        if (is_file($path)) {
            $adder = $model->addMedia($path);

            if ($preserveOriginal) {
                $adder = $adder->preservingOriginal();
            }

            $adder->toMediaCollection($collection);

            return;
        }

        $model->addMediaFromString(base64_decode(self::PLACEHOLDER_PNG))
            ->usingFileName(str_replace('.webp', '.png', $filename))
            ->toMediaCollection($collection);
    }
}
