<?php

use App\Models\Alert;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GovService;
use App\Models\Incident;
use App\Models\Revision;
use App\Models\User;
use App\Services\Admin\RevisionDiffBuilder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed([LanguageSeeder::class, RolePermissionSeeder::class]);
    $this->user = User::factory()->withTwoFactor()->create();
    $this->user->assignRole('super-admin');
});

it('creates a revision when a gallery is updated', function () {
    $gallery = Gallery::factory()->create();
    $gallery->upsertTranslations([
        'ru' => ['title' => 'Галерея 1', 'slug' => 'galereya-1'],
    ]);
    $gallery->saveRevision();

    expect(Revision::query()->where('revisionable_type', Gallery::class)->count())->toBe(1);
});

it('creates a revision when an faq is updated', function () {
    $faq = Faq::factory()->create();
    $faq->upsertTranslations([
        'ru' => ['question' => 'Вопрос?', 'answer' => '<p>Ответ</p>'],
    ]);
    $faq->saveRevision();

    expect(Revision::query()->where('revisionable_type', Faq::class)->count())->toBe(1);
});

it('creates a revision when a government service is updated', function () {
    $service = GovService::factory()->create();
    $service->upsertTranslations([
        'ru' => ['title' => 'Услуга', 'slug' => 'usluga-ru'],
    ]);
    $service->saveRevision();

    expect(Revision::query()->where('revisionable_type', GovService::class)->count())->toBe(1);
});

it('builds a diff between two revision payloads', function () {
    $builder = app(RevisionDiffBuilder::class);

    $changes = $builder->diff(
        [
            'attributes' => ['status' => 'draft', 'sort_order' => 1],
            'translations' => [
                ['locale' => 'ru', 'title' => 'Старый заголовок'],
            ],
        ],
        [
            'attributes' => ['status' => 'published', 'sort_order' => 1],
            'translations' => [
                ['locale' => 'ru', 'title' => 'Новый заголовок'],
            ],
        ],
    );

    expect($changes)->toHaveCount(2)
        ->and(collect($changes)->pluck('field')->all())->toEqualCanonicalizing(['status', 'title']);
});

it('returns revision diff via api', function () {
    $faq = Faq::factory()->create(['status' => 'draft']);
    $faq->upsertTranslations([
        'ru' => ['question' => 'Старый вопрос', 'answer' => '<p>Старый ответ</p>'],
    ]);
    $older = $faq->saveRevision();

    $faq->update(['status' => 'published']);
    $faq->upsertTranslations([
        'ru' => ['question' => 'Новый вопрос', 'answer' => '<p>Новый ответ</p>'],
    ]);
    $faq->saveRevision();

    $this->actingAs($this->user)
        ->get(route('admin.revisions.show', $older))
        ->assertOk()
        ->assertJsonPath('compare_label', 'Следующая версия')
        ->assertJson(fn ($json) => $json
            ->has('changes', 3)
            ->etc()
        );
});

it('fetches faq revisions via api', function () {
    $faq = Faq::factory()->create();
    $faq->upsertTranslations([
        'ru' => ['question' => 'Вопрос', 'answer' => '<p>Ответ</p>'],
    ]);
    $faq->saveRevision();

    $this->actingAs($this->user)
        ->get('/admin/revisions/faq/'.$faq->id)
        ->assertOk()
        ->assertJsonCount(1);
});

it('creates a revision when an incident is updated', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations([
        'ru' => ['title' => 'Землетрясение', 'description' => 'Описание'],
    ]);
    $incident->saveRevision();

    expect(Revision::query()->where('revisionable_type', Incident::class)->count())->toBe(1);
});

it('creates a revision when an alert is updated', function () {
    $alert = Alert::factory()->create();
    $alert->upsertTranslations([
        'ru' => ['title' => 'Оповещение', 'body' => 'Текст'],
    ]);
    $alert->saveRevision();

    expect(Revision::query()->where('revisionable_type', Alert::class)->count())->toBe(1);
});

it('restores an incident revision including translations', function () {
    $incident = Incident::factory()->create(['status' => 'active']);
    $incident->upsertTranslations([
        'ru' => ['title' => 'Старое событие', 'description' => 'Старое описание'],
    ]);
    $revision = $incident->saveRevision();

    $incident->update(['status' => 'resolved']);
    $incident->upsertTranslations([
        'ru' => ['title' => 'Новое событие', 'description' => 'Новое описание'],
    ]);

    $incident->restoreRevision($revision);
    $incident->refresh()->load('translations');

    expect($incident->status->value)->toBe('active')
        ->and($incident->translation('ru')->title)->toBe('Старое событие');
});

it('fetches incident revisions via api', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations([
        'ru' => ['title' => 'Событие', 'description' => 'Описание'],
    ]);
    $incident->saveRevision();

    $this->actingAs($this->user)
        ->get('/admin/revisions/incident/'.$incident->id)
        ->assertOk()
        ->assertJsonCount(1);
});

it('fetches alert revisions via api', function () {
    $alert = Alert::factory()->create();
    $alert->upsertTranslations([
        'ru' => ['title' => 'Оповещение', 'body' => 'Текст'],
    ]);
    $alert->saveRevision();

    $this->actingAs($this->user)
        ->get('/admin/revisions/alert/'.$alert->id)
        ->assertOk()
        ->assertJsonCount(1);
});

it('forbids revision index without manage permission for that content type', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations([
        'ru' => ['title' => 'Событие', 'description' => 'Описание'],
    ]);
    $incident->saveRevision();

    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)
        ->get(route('admin.revisions.index', ['type' => 'incident', 'id' => $incident->id]))
        ->assertForbidden();
});

it('forbids revision show without manage permission for that content type', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations([
        'ru' => ['title' => 'Событие', 'description' => 'Описание'],
    ]);
    $revision = $incident->saveRevision();

    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)
        ->get(route('admin.revisions.show', $revision))
        ->assertForbidden();
});
