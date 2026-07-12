<?php

use App\Enums\Role;
use App\Models\Alert;
use App\Models\Document;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Guide;
use App\Models\Incident;
use App\Models\Leader;
use App\Models\Page;
use App\Models\Poll;
use App\Models\Post;
use App\Models\Tender;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, LanguageSeeder::class]);
    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

it('renders the shared entry form for faq create and edit', function () {
    $faq = Faq::factory()->create();
    $faq->upsertTranslations([
        'tj' => ['question' => 'Савол?', 'answer' => 'Ҷавоб'],
    ]);

    $this->actingAs($this->editor)
        ->get(route('admin.faqs.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'faq')
            ->where('contentType.titleField', 'question')
            ->where('entry', null)
            ->has('urls.store')
            ->where('urls.update', null)
            ->has('blueprint')
            ->has('locales')
        );

    $this->actingAs($this->editor)
        ->get(route('admin.faqs.edit', $faq))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'faq')
            ->where('entry.id', $faq->id)
            ->has('urls.update')
            ->where('entry.translations.tj.question', 'Савол?')
        );
});

it('renders the shared entry form for media types with asset meta', function (
    string $createRoute,
    string $editRoute,
    string $handle,
    string $modelClass,
    string $titleField,
    string $assetKey,
) {
    $model = $modelClass::factory()->create();

    match ($handle) {
        'document' => $model->upsertTranslations(['tj' => ['name' => 'Қонун']]),
        'guide' => $model->upsertTranslations(['tj' => ['title' => 'Памятка', 'slug' => 'pamyatka']]),
        'gallery' => $model->upsertTranslations(['tj' => ['title' => 'Галерея', 'slug' => 'galereya']]),
        'leader' => $model->upsertTranslations(['tj' => ['full_name' => 'Ном', 'position' => 'Вазифа']]),
    };

    $this->actingAs($this->editor)
        ->get(route($createRoute))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', $handle)
            ->where('contentType.titleField', $titleField)
            ->where('entry', null)
            ->has($assetKey)
            ->has('blueprint')
            ->has('locales')
        );

    $this->actingAs($this->editor)
        ->get(route($editRoute, $model))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', $handle)
            ->where('entry.id', $model->id)
            ->has('urls.update')
            ->has($assetKey)
        );
})->with([
    'document' => ['admin.documents.create', 'admin.documents.edit', 'document', Document::class, 'name', 'existingFiles'],
    'guide' => ['admin.guides.create', 'admin.guides.edit', 'guide', Guide::class, 'title', 'existingFiles'],
    'gallery' => ['admin.gallery.create', 'admin.gallery.edit', 'gallery', Gallery::class, 'title', 'existingPhotos'],
    'leader' => ['admin.leadership.create', 'admin.leadership.edit', 'leader', Leader::class, 'full_name', 'photoUrl'],
]);

it('renders the shared entry form for schedulable types', function (
    string $createRoute,
    string $editRoute,
    string $handle,
    string $modelClass,
) {
    $model = $modelClass::factory()->create();
    $model->upsertTranslations(['tj' => ['title' => 'Сарлавҳа', 'slug' => "{$handle}-slug"]]);

    $this->actingAs($this->editor)
        ->get(route($createRoute))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', $handle)
            ->where('contentType.titleField', 'title')
            ->where('contentType.features', fn ($features) => collect($features)->contains('schedulable'))
            ->where('entry', null)
            ->has('blueprint')
            ->has('locales')
        );

    $this->actingAs($this->editor)
        ->get(route($editRoute, $model))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', $handle)
            ->where('entry.id', $model->id)
            ->has('urls.update')
            ->where('entry.translations.tj.title', 'Сарлавҳа')
        );
})->with([
    'vacancy' => ['admin.vacancies.create', 'admin.vacancies.edit', 'vacancy', Vacancy::class],
    'tender' => ['admin.tenders.create', 'admin.tenders.edit', 'tender', Tender::class],
]);

it('renders the shared entry form for polls with options', function () {
    $poll = Poll::factory()->create();
    $poll->upsertTranslations(['tj' => ['title' => 'Пурсиш', 'slug' => 'pursish']]);

    $option = $poll->options()->create(['sort_order' => 0]);
    $option->upsertTranslations(['tj' => ['label' => 'Ҳа']]);
    $poll->options()->create(['sort_order' => 1])->upsertTranslations(['tj' => ['label' => 'Не']]);

    $this->actingAs($this->editor)
        ->get(route('admin.polls.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'poll')
            ->where('entry', null)
            ->has('blueprint')
            ->has('fieldOptions.type')
        );

    $this->actingAs($this->editor)
        ->get(route('admin.polls.edit', $poll))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'poll')
            ->where('entry.id', $poll->id)
            ->has('entry.options', 2)
            ->where('entry.options.0.translations.tj.label', 'Ҳа')
            ->has('entry.total_votes')
            ->has('urls.update')
        );
});

it('renders the shared entry form for alerts with estimate url', function () {
    $alert = Alert::factory()->create();
    $alert->upsertTranslations(['tj' => ['title' => 'Огоҳӣ', 'body' => 'Матн']]);

    $this->actingAs($this->editor)
        ->get(route('admin.alerts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'alert')
            ->where('entry', null)
            ->has('urls.estimate')
            ->has('fieldOptions.status')
            ->has('fieldOptions.hazard_level')
        );

    $this->actingAs($this->editor)
        ->get(route('admin.alerts.edit', $alert))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'alert')
            ->where('entry.id', $alert->id)
            ->where('entry.translations.tj.title', 'Огоҳӣ')
            ->has('urls.estimate')
            ->has('urls.update')
        );
});

it('renders the shared entry form for incidents with map coordinates', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations(['tj' => ['title' => 'Ҳодиса', 'description' => 'Тавсиф']]);

    $this->actingAs($this->editor)
        ->get(route('admin.incidents.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'incident')
            ->where('entry', null)
            ->has('regionCoordinates')
            ->has('fieldOptions.status')
            ->has('fieldOptions.type')
        );

    $this->actingAs($this->editor)
        ->get(route('admin.incidents.edit', $incident))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/form')
            ->where('contentType.handle', 'incident')
            ->where('entry.id', $incident->id)
            ->where('entry.translations.tj.title', 'Ҳодиса')
            ->has('regionCoordinates')
            ->has('urls.update')
        );
});

it('renders the editorial form for posts and pages', function (
    string $createRoute,
    string $editRoute,
    string $handle,
    string $modelClass,
    bool $expectsBlockset,
) {
    $model = $modelClass::factory()->create();
    $model->upsertTranslations(['tj' => ['title' => 'Сарлавҳа', 'slug' => "{$handle}-slug"]]);
    $model->load(['translations', 'media', 'tags']);

    $this->actingAs($this->editor)
        ->get(route($createRoute))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content/editorial-form')
            ->where('contentType.handle', $handle)
            ->where('entry', null)
            ->has('blueprint')
            ->where('urls.autosave', null)
        );

    $this->actingAs($this->editor)
        ->get(route($editRoute, $model))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($handle, $model, $expectsBlockset) {
            $page
                ->component('admin/content/editorial-form')
                ->where('contentType.handle', $handle)
                ->where('entry.id', $model->id)
                ->has('urls.autosave')
                ->has('urls.publishVersion')
                ->has('previewUrls')
                ->has('publicUrls');

            if ($expectsBlockset) {
                $page->has('blockset');
            }

            return $page;
        });
})->with([
    'post' => ['admin.posts.create', 'admin.posts.edit', 'post', Post::class, false],
    'page' => ['admin.pages.create', 'admin.pages.edit', 'page', Page::class, true],
]);
