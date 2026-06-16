<?php

use App\Enums\AppealStatus;
use App\Enums\Role;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
    Storage::fake('local');
});

function openVacancy(string $slug = 'spasatel', string $locale = 'tj'): Vacancy
{
    $vacancy = Vacancy::factory()->create();
    $vacancy->upsertTranslations([$locale => ['title' => 'Спасатель', 'slug' => $slug]]);

    return $vacancy;
}

function applicationForm(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Иван Иванов',
        'email' => 'ivan@example.com',
        'phone' => '+992900000000',
        'cover_letter' => 'Прошу рассмотреть мою кандидатуру.',
        'resume' => UploadedFile::fake()->create('cv.pdf', 120, 'application/pdf'),
        'website' => '',
    ], $overrides);
}

it('renders the public vacancies list with open vacancies', function () {
    openVacancy();

    $this->get(route('vacancies.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/vacancies/index')->has('vacancies.data', 1));
});

it('lists only published, open vacancies', function () {
    openVacancy('open-vac');

    $draft = Vacancy::factory()->draft()->create();
    $draft->upsertTranslations(['tj' => ['title' => 'Черновик', 'slug' => 'draft-vac']]);

    $closed = Vacancy::factory()->closed()->create();
    $closed->upsertTranslations(['tj' => ['title' => 'Закрыто', 'slug' => 'closed-vac']]);

    $this->get(route('vacancies.index', ['locale' => 'tj']))
        ->assertInertia(fn (Assert $page) => $page->has('vacancies.data', 1));
});

it('shows a vacancy by its localized slug', function () {
    $vacancy = openVacancy();

    $this->get(route('vacancies.show', ['locale' => 'tj', 'slug' => 'spasatel']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/vacancies/show')
            ->where('vacancy.id', $vacancy->id)
            ->where('vacancy.is_open', true)
        );
});

it('accepts an online application with a CV and assigns a reference', function () {
    $vacancy = openVacancy();

    $this->post(route('vacancies.apply', ['locale' => 'tj', 'vacancy' => $vacancy->id]), applicationForm())
        ->assertRedirect(route('vacancies.show', ['locale' => 'tj', 'slug' => 'spasatel']))
        ->assertSessionHas('application_reference');

    $application = VacancyApplication::first();
    $resume = $application?->getFirstMedia(VacancyApplication::RESUME_COLLECTION);

    expect($application)->not->toBeNull()
        ->and($application->status)->toBe(AppealStatus::New)
        ->and($application->reference)->toStartWith('VAC-')
        ->and($application->vacancy_id)->toBe($vacancy->id)
        ->and($resume)->not->toBeNull()
        ->and($resume->disk)->toBe('local');
});

it('rejects an application with the honeypot filled', function () {
    $vacancy = openVacancy();

    $this->post(route('vacancies.apply', ['locale' => 'tj', 'vacancy' => $vacancy->id]), applicationForm(['website' => 'http://spam']))
        ->assertSessionHasErrors('website');

    expect(VacancyApplication::count())->toBe(0);
});

it('validates required application fields including the CV', function () {
    $vacancy = openVacancy();

    $this->post(route('vacancies.apply', ['locale' => 'tj', 'vacancy' => $vacancy->id]), applicationForm([
        'full_name' => '',
        'email' => '',
        'resume' => null,
    ]))->assertSessionHasErrors(['full_name', 'email', 'resume']);
});

it('rejects an application to a closed vacancy', function () {
    $closed = Vacancy::factory()->closed()->create();
    $closed->upsertTranslations(['tj' => ['title' => 'Закрыто', 'slug' => 'closed-vac']]);

    $this->post(route('vacancies.apply', ['locale' => 'tj', 'vacancy' => $closed->id]), applicationForm())
        ->assertNotFound();

    expect(VacancyApplication::count())->toBe(0);
});

it('tracks an application by reference', function () {
    $application = VacancyApplication::factory()->create();

    $this->get(route('vacancies.track', ['locale' => 'tj', 'reference' => $application->reference]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/vacancies/track')
            ->where('result.found', true)
        );

    $this->get(route('vacancies.track', ['locale' => 'tj', 'reference' => 'VAC-2026-NOPE00']))
        ->assertInertia(fn (Assert $page) => $page->where('result.found', false));
});

it('restricts the CMS applications queue to staff with permission', function () {
    $this->get(route('admin.vacancy-applications.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.vacancy-applications.index'))
        ->assertForbidden();
});

it('lets a moderator view, update and download an application', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $vacancy = openVacancy('spasatel-ru', 'ru');
    $application = VacancyApplication::factory()->create(['vacancy_id' => $vacancy->id]);
    $application->addMedia(UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
        ->toMediaCollection(VacancyApplication::RESUME_COLLECTION);

    $this->actingAs($moderator)->get(route('admin.vacancy-applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/vacancy-applications/index')->has('applications.data', 1));

    $this->actingAs($moderator)
        ->put(route('admin.vacancy-applications.update', $application), [
            'status' => 'in_progress',
            'assigned_to' => $moderator->id,
            'internal_note' => 'Взято в работу',
        ])
        ->assertRedirect(route('admin.vacancy-applications.show', $application));

    expect($application->fresh()->status)->toBe(AppealStatus::InProgress)
        ->and($application->fresh()->assigned_to)->toBe($moderator->id);

    $this->actingAs($moderator)->get(route('admin.vacancy-applications.resume', $application))->assertOk();
});
