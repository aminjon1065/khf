<?php

use App\Enums\ContentStatus;
use App\Enums\EmploymentType;
use App\Enums\Role;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function vacancyPayload(array $overrides = []): array
{
    return array_merge([
        'employment_type' => 'full_time',
        'status' => 'published',
        'positions_count' => 2,
        'published_at' => '2026-06-16T10:00',
        'deadline_at' => '2026-12-31',
        'translations' => [
            'tj' => ['title' => 'Мутахассиси калон', 'slug' => 'mutaxassisi-kalon', 'department' => 'Раёсат', 'summary' => 'Шарҳ', 'description' => 'Матн', 'requirements' => 'Талабот'],
            'ru' => ['title' => 'Главный специалист', 'slug' => 'glavnyy-specialist', 'department' => 'Управление', 'summary' => 'Описание', 'description' => 'Текст', 'requirements' => 'Требования'],
            'en' => ['title' => '', 'slug' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.vacancies.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.vacancies.index'))
        ->assertForbidden();
});

it('creates a vacancy and sets the creator', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.vacancies.store'), vacancyPayload())
        ->assertRedirect(route('admin.vacancies.index'));

    $vacancy = Vacancy::with('translations')->first();

    expect($vacancy->employment_type)->toBe(EmploymentType::FullTime)
        ->and($vacancy->status)->toBe(ContentStatus::Published)
        ->and($vacancy->positions_count)->toBe(2)
        ->and($vacancy->created_by)->toBe($this->editor->id)
        ->and($vacancy->translations)->toHaveCount(2)
        ->and($vacancy->deadline_at)->not->toBeNull();
});

it('validates employment type, positions count and the default-locale title', function () {
    $payload = vacancyPayload(['employment_type' => 'invalid', 'positions_count' => 0]);
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.vacancies.create'))
        ->post(route('admin.vacancies.store'), $payload)
        ->assertSessionHasErrors(['employment_type', 'positions_count', 'translations.tj.title']);
});

it('enforces slug uniqueness within a locale', function () {
    $this->actingAs($this->editor)->post(route('admin.vacancies.store'), vacancyPayload());

    $second = vacancyPayload();
    $second['translations']['tj']['slug'] = 'another-slug';

    $this->actingAs($this->editor)
        ->from(route('admin.vacancies.create'))
        ->post(route('admin.vacancies.store'), $second)
        ->assertSessionHasErrors('translations.ru.slug');
});

it('renders the list, create, edit and trash screens', function () {
    $vacancy = Vacancy::factory()->create(['created_by' => $this->editor->id]);
    $vacancy->upsertTranslations(['tj' => ['title' => 'Тест', 'slug' => 'test-vacancy']]);

    $this->actingAs($this->editor)->get(route('admin.vacancies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/vacancies/index')->has('vacancies.data', 1));

    $this->actingAs($this->editor)->get(route('admin.vacancies.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/vacancies/form')
            ->has('blueprint')
            ->has('fieldOptions.employment_type', 4)
            ->has('statuses', 4));

    $this->actingAs($this->editor)->get(route('admin.vacancies.edit', $vacancy))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/vacancies/form')->where('vacancy.id', $vacancy->id));

    $vacancy->delete();

    $this->actingAs($this->editor)->get(route('admin.vacancies.trash'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/vacancies/trash')->has('vacancies.data', 1));
});

it('sanitizes the vacancy description html on save', function () {
    $payload = vacancyPayload();
    $payload['translations']['tj']['description'] = '<p>Безопасный текст</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="hack()">ссылка</a>';

    $this->actingAs($this->editor)->post(route('admin.vacancies.store'), $payload);

    $description = Vacancy::first()->translation('tj')->description;

    expect($description)
        ->toContain('Безопасный текст')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:');
});

it('soft deletes, restores and force deletes a vacancy', function () {
    $vacancy = Vacancy::factory()->create(['created_by' => $this->editor->id]);
    $vacancy->upsertTranslations(['tj' => ['title' => 'В', 'slug' => 'v-del']]);

    $this->actingAs($this->editor)->delete(route('admin.vacancies.destroy', $vacancy));
    expect(Vacancy::count())->toBe(0)->and(Vacancy::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->editor)->patch(route('admin.vacancies.restore', $vacancy));
    expect(Vacancy::count())->toBe(1);

    $this->actingAs($this->editor)->delete(route('admin.vacancies.destroy', $vacancy));
    $this->actingAs($this->editor)->delete(route('admin.vacancies.force-delete', $vacancy));
    expect(Vacancy::withTrashed()->count())->toBe(0);
});
