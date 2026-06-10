<?php

use App\Enums\AppealStatus;
use App\Enums\Role;
use App\Models\TouristGroup;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
});

function groupForm(array $overrides = []): array
{
    return array_merge([
        'leader_name' => 'Руководитель',
        'leader_phone' => '+992900000000',
        'leader_email' => 'leader@example.com',
        'participants_count' => 5,
        'route' => 'Маршрут к озеру Искандеркуль',
        'equipment' => 'Палатки, верёвки',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-05',
        'region_id' => null,
        'website' => '',
    ], $overrides);
}

it('renders the public registration form', function () {
    $this->get(route('tourist-groups.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/tourist-groups/create')->has('regions'));
});

it('registers a tourist group and assigns a reference', function () {
    $this->post(route('tourist-groups.store', ['locale' => 'tj']), groupForm())
        ->assertRedirect(route('tourist-groups.create', ['locale' => 'tj']))
        ->assertSessionHas('tourist_group_reference');

    $group = TouristGroup::first();

    expect($group)->not->toBeNull()
        ->and($group->status)->toBe(AppealStatus::New)
        ->and($group->reference)->toStartWith('TUR-')
        ->and($group->participants_count)->toBe(5);
});

it('rejects spam via the honeypot', function () {
    $this->post(route('tourist-groups.store', ['locale' => 'tj']), groupForm(['website' => 'spam']))
        ->assertSessionHasErrors('website');

    expect(TouristGroup::count())->toBe(0);
});

it('validates dates and required fields', function () {
    $this->post(route('tourist-groups.store', ['locale' => 'tj']), groupForm([
        'leader_name' => '',
        'end_date' => '2026-06-01', // before start_date
    ]))->assertSessionHasErrors(['leader_name', 'end_date']);
});

it('tracks a tourist group by reference', function () {
    $group = TouristGroup::factory()->create();

    $this->get(route('tourist-groups.track', ['locale' => 'tj', 'reference' => $group->reference]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('result.found', true));

    $this->get(route('tourist-groups.track', ['locale' => 'tj', 'reference' => 'TUR-2026-NONE00']))
        ->assertInertia(fn (Assert $page) => $page->where('result.found', false));
});

it('restricts the CMS queue to staff with permission', function () {
    $this->get(route('admin.tourist-groups.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.tourist-groups.index'))
        ->assertForbidden();
});

it('lets a moderator view and update an application', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);
    $group = TouristGroup::factory()->create();

    $this->actingAs($moderator)->get(route('admin.tourist-groups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/tourist-groups/index')->has('groups.data', 1));

    $this->actingAs($moderator)
        ->put(route('admin.tourist-groups.update', $group), [
            'status' => 'answered',
            'assigned_to' => $moderator->id,
            'internal_note' => 'Согласовано',
        ])
        ->assertRedirect(route('admin.tourist-groups.show', $group));

    expect($group->fresh()->status)->toBe(AppealStatus::Answered);
});
