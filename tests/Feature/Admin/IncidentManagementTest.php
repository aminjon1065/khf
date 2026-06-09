<?php

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\Role;
use App\Models\Incident;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->operator = User::factory()->withTwoFactor()->create();
    $this->operator->assignRole(Role::Moderator->value);
});

function incidentPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'earthquake',
        'hazard_level' => 'danger',
        'status' => 'active',
        'region_id' => null,
        'latitude' => 38.5598,
        'longitude' => 68.7870,
        'occurred_at' => '2026-06-09T08:00',
        'translations' => [
            'tj' => ['title' => 'Заминларза', 'description' => 'Тавсиф'],
            'ru' => ['title' => 'Землетрясение', 'description' => 'Описание'],
            'en' => ['title' => '', 'description' => ''],
        ],
    ], $overrides);
}

it('redirects guests to login', function () {
    $this->get(route('admin.incidents.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.incidents.index'))
        ->assertForbidden();
});

it('creates an incident with translations', function () {
    $this->actingAs($this->operator)
        ->post(route('admin.incidents.store'), incidentPayload())
        ->assertRedirect(route('admin.incidents.index'));

    $incident = Incident::with('translations')->first();

    expect($incident->type)->toBe(IncidentType::Earthquake)
        ->and($incident->hazard_level)->toBe(HazardLevel::Danger)
        ->and($incident->status)->toBe(IncidentStatus::Active)
        ->and($incident->translations)->toHaveCount(2)
        ->and($incident->translation('ru')->title)->toBe('Землетрясение')
        ->and((float) $incident->latitude)->toBe(38.5598);
});

it('validates type, hazard level and default title', function () {
    $payload = incidentPayload(['type' => 'meteor', 'hazard_level' => 'mega']);
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->operator)
        ->from(route('admin.incidents.create'))
        ->post(route('admin.incidents.store'), $payload)
        ->assertSessionHasErrors(['type', 'hazard_level', 'translations.tj.title']);
});

it('renders the list, create, edit and trash screens', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations(['tj' => ['title' => 'Тест', 'description' => 'd']]);

    $this->actingAs($this->operator)->get(route('admin.incidents.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/incidents/index')->has('incidents.data', 1));

    $this->actingAs($this->operator)->get(route('admin.incidents.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/incidents/form')->has('types', 7)->has('levels', 4));

    $this->actingAs($this->operator)->get(route('admin.incidents.edit', $incident))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/incidents/form')->where('incident.id', $incident->id));

    $incident->delete();

    $this->actingAs($this->operator)->get(route('admin.incidents.trash'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->component('admin/incidents/trash')->has('incidents.data', 1));
});

it('soft deletes, restores and force deletes an incident', function () {
    $incident = Incident::factory()->create();
    $incident->upsertTranslations(['tj' => ['title' => 'Т']]);

    $this->actingAs($this->operator)->delete(route('admin.incidents.destroy', $incident));
    expect(Incident::count())->toBe(0)->and(Incident::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->operator)->patch(route('admin.incidents.restore', $incident));
    expect(Incident::count())->toBe(1);

    $this->actingAs($this->operator)->delete(route('admin.incidents.destroy', $incident));
    $this->actingAs($this->operator)->delete(route('admin.incidents.force-delete', $incident));
    expect(Incident::withTrashed()->count())->toBe(0);
});

it('shows the public incidents archive with active events first', function () {
    $resolved = Incident::factory()->resolved()->create(['occurred_at' => now()->subDay()]);
    $resolved->upsertTranslations(['tj' => ['title' => 'Завершено']]);

    $active = Incident::factory()->create(['status' => IncidentStatus::Active, 'occurred_at' => now()->subDays(2)]);
    $active->upsertTranslations(['tj' => ['title' => 'Активно']]);

    $this->get(route('incidents.index', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('public/incidents/index')
            ->has('incidents.data', 2)
            ->where('incidents.data.0.title', 'Активно')
        );
});
