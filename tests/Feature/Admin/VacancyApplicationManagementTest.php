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

    $this->moderator = User::factory()->withTwoFactor()->create();
    $this->moderator->assignRole(Role::Moderator->value);
});

it('redirects guests to login', function () {
    $this->get(route('admin.vacancy-applications.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.vacancy-applications.index'))
        ->assertForbidden();
});

it('renders the applications queue and show screen', function () {
    $vacancy = Vacancy::factory()->create();
    $vacancy->upsertTranslations(['ru' => ['title' => 'Спасатель', 'slug' => 'spasatel-admin']]);
    $application = VacancyApplication::factory()->create(['vacancy_id' => $vacancy->id]);

    $this->actingAs($this->moderator)
        ->get(route('admin.vacancy-applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/vacancy-applications/index')
            ->has('applications.data', 1)
        );

    $this->actingAs($this->moderator)
        ->get(route('admin.vacancy-applications.show', $application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/vacancy-applications/show')
            ->where('application.id', $application->id)
            ->where('application.full_name', $application->full_name)
            ->has('staff')
        );
});

it('updates and deletes an application', function () {
    $application = VacancyApplication::factory()->create();

    $this->actingAs($this->moderator)
        ->put(route('admin.vacancy-applications.update', $application), [
            'status' => AppealStatus::InProgress->value,
            'assigned_to' => $this->moderator->id,
            'internal_note' => 'Проверка',
        ])
        ->assertRedirect(route('admin.vacancy-applications.show', $application));

    expect($application->fresh()->status)->toBe(AppealStatus::InProgress)
        ->and($application->fresh()->assigned_to)->toBe($this->moderator->id);

    $this->actingAs($this->moderator)
        ->delete(route('admin.vacancy-applications.destroy', $application))
        ->assertRedirect(route('admin.vacancy-applications.index'));

    expect(VacancyApplication::query()->find($application->id))->toBeNull();
});

it('serves the resume to authorised staff', function () {
    $application = VacancyApplication::factory()->create();
    $application->addMedia(UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
        ->toMediaCollection(VacancyApplication::RESUME_COLLECTION);

    $this->actingAs($this->moderator)
        ->get(route('admin.vacancy-applications.resume', $application))
        ->assertOk();
});
