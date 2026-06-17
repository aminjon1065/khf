<?php

use App\Enums\Role;
use App\Models\Statistic;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function statisticPayload(array $overrides = []): array
{
    return array_merge([
        'status' => 'published',
        'value' => '1234',
        'year' => 2025,
        'sort_order' => 0,
        'translations' => [
            'tj' => ['label' => 'Амалиётҳои наҷотдиҳӣ', 'unit' => 'адад'],
            'ru' => ['label' => 'Спасательных операций', 'unit' => 'ед.'],
            'en' => ['label' => '', 'unit' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.statistics.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.statistics.index'))
        ->assertForbidden();
});

it('renders the statistics list and form', function () {
    $statistic = Statistic::factory()->create();
    $statistic->upsertTranslations(['tj' => ['label' => 'Тест']]);

    $this->actingAs($this->editor)->get(route('admin.statistics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/statistics/index')->has('statistics.data', 1));

    $this->actingAs($this->editor)->get(route('admin.statistics.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/statistics/form')->has('locales', 3)->has('statuses', 4));
});

it('creates an indicator with translations', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.statistics.store'), statisticPayload())
        ->assertRedirect(route('admin.statistics.index'));

    $statistic = Statistic::with('translations')->first();

    expect($statistic->value)->toBe('1234')
        ->and($statistic->year)->toBe(2025)
        ->and($statistic->translations)->toHaveCount(2)
        ->and($statistic->translation('ru')->label)->toBe('Спасательных операций');
});

it('requires the value and the default-locale label', function () {
    $payload = statisticPayload(['value' => '']);
    $payload['translations']['tj']['label'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.statistics.create'))
        ->post(route('admin.statistics.store'), $payload)
        ->assertSessionHasErrors(['value', 'translations.tj.label']);
});

it('updates and deletes an indicator', function () {
    $statistic = Statistic::factory()->create();
    $statistic->upsertTranslations(['tj' => ['label' => 'Старый']]);

    $this->actingAs($this->editor)
        ->put(route('admin.statistics.update', $statistic), statisticPayload(['sort_order' => 9]))
        ->assertRedirect(route('admin.statistics.index'));

    expect($statistic->fresh()->sort_order)->toBe(9);

    $this->actingAs($this->editor)
        ->delete(route('admin.statistics.destroy', $statistic))
        ->assertRedirect(route('admin.statistics.index'));

    expect(Statistic::find($statistic->id))->toBeNull();
});
