<?php

use App\Enums\Role;
use App\Models\Appeal;
use App\Models\Subscriber;
use App\Models\TenderBid;
use App\Models\TouristGroup;
use App\Models\User;
use App\Models\VacancyApplication;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
});

/**
 * @return array{0: User, 1: User}
 */
function piiStaffPair(): array
{
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $editor = User::factory()->withTwoFactor()->create();
    $editor->assignRole(Role::Editor->value);

    return [$moderator, $editor];
}

it('allows moderators and forbids editors from the appeals inbox', function () {
    [$moderator, $editor] = piiStaffPair();
    $appeal = Appeal::factory()->create();

    expect($moderator->can('viewAny', Appeal::class))->toBeTrue()
        ->and($moderator->can('view', $appeal))->toBeTrue()
        ->and($moderator->can('update', $appeal))->toBeTrue()
        ->and($moderator->can('delete', $appeal))->toBeTrue()
        ->and($moderator->can('export', Appeal::class))->toBeTrue()
        ->and($editor->can('viewAny', Appeal::class))->toBeFalse()
        ->and($editor->can('update', $appeal))->toBeFalse();

    $this->actingAs($editor)->get(route('admin.appeals.index'))->assertForbidden();
    $this->actingAs($editor)->get(route('admin.appeals.show', $appeal))->assertForbidden();
    $this->actingAs($editor)->put(route('admin.appeals.update', $appeal), [
        'status' => 'in_progress',
        'assigned_to' => null,
        'internal_note' => null,
        'deadline_at' => null,
    ])->assertForbidden();
    $this->actingAs($editor)->delete(route('admin.appeals.destroy', $appeal))->assertForbidden();

    $this->actingAs($moderator)->get(route('admin.appeals.index'))->assertOk();
    $this->actingAs($moderator)->get(route('admin.appeals.show', $appeal))->assertOk();
});

it('allows moderators and forbids editors from tourist-group PII', function () {
    [$moderator, $editor] = piiStaffPair();
    $group = TouristGroup::factory()->create();

    expect($moderator->can('view', $group))->toBeTrue()
        ->and($editor->can('delete', $group))->toBeFalse();

    $this->actingAs($editor)->get(route('admin.tourist-groups.index'))->assertForbidden();
    $this->actingAs($editor)->delete(route('admin.tourist-groups.destroy', $group))->assertForbidden();
    $this->actingAs($moderator)->get(route('admin.tourist-groups.show', $group))->assertOk();
});

it('allows moderators and forbids editors from the subscriber registry', function () {
    [$moderator, $editor] = piiStaffPair();
    $subscriber = Subscriber::factory()->create();

    expect($moderator->can('viewAny', Subscriber::class))->toBeTrue()
        ->and($editor->can('delete', $subscriber))->toBeFalse();

    $this->actingAs($editor)->get(route('admin.subscribers.index'))->assertForbidden();
    $this->actingAs($editor)->delete(route('admin.subscribers.destroy', $subscriber))->assertForbidden();
    $this->actingAs($moderator)->delete(route('admin.subscribers.destroy', $subscriber))
        ->assertRedirect(route('admin.subscribers.index'));
});

it('allows moderators and forbids editors from tender bid PII', function () {
    [$moderator, $editor] = piiStaffPair();
    $bid = TenderBid::factory()->create();

    $this->actingAs($editor)->get(route('admin.tender-bids.show', $bid))->assertForbidden();
    $this->actingAs($editor)->put(route('admin.tender-bids.update', $bid), [
        'status' => 'in_progress',
        'assigned_to' => null,
        'internal_note' => null,
    ])->assertForbidden();
    $this->actingAs($moderator)->get(route('admin.tender-bids.show', $bid))->assertOk();
});

it('allows moderators and forbids editors from vacancy application PII', function () {
    [$moderator, $editor] = piiStaffPair();
    $application = VacancyApplication::factory()->create();

    $this->actingAs($editor)->get(route('admin.vacancy-applications.show', $application))->assertForbidden();
    $this->actingAs($editor)->delete(route('admin.vacancy-applications.destroy', $application))->assertForbidden();
    $this->actingAs($moderator)->get(route('admin.vacancy-applications.show', $application))->assertOk();
});

it('lets the super-admin bypass PII policies via Gate::before', function () {
    $admin = User::factory()->withTwoFactor()->create();
    $admin->assignRole(Role::SuperAdmin->value);
    $appeal = Appeal::factory()->create();

    expect($admin->can('view', $appeal))->toBeTrue()
        ->and($admin->can('export', Appeal::class))->toBeTrue();

    $this->actingAs($admin)->get(route('admin.appeals.export'))->assertOk();
});
