<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('seeds every permission and the two roles', function () {
    expect(PermissionModel::count())->toBe(count(Permission::cases()))
        ->and(RoleModel::count())->toBe(count(Role::cases()))
        ->and(RoleModel::pluck('name')->all())->toEqualCanonicalizing(['super-admin', 'moderator']);
});

it('grants the super admin every ability via the gate', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::SuperAdmin->value);

    expect($user->can(Permission::ManageUsers->value))->toBeTrue()
        ->and($user->can(Permission::ManageRoles->value))->toBeTrue()
        ->and($user->can(Permission::SendAlerts->value))->toBeTrue()
        ->and($user->can('some.undefined.ability'))->toBeTrue();
});

it('gives the moderator content and operations but not user, role or settings management', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Moderator->value);

    expect($user->can(Permission::ManagePosts->value))->toBeTrue()
        ->and($user->can(Permission::SendAlerts->value))->toBeTrue()
        ->and($user->can(Permission::ManageAppeals->value))->toBeTrue()
        ->and($user->can(Permission::ManageMedia->value))->toBeTrue()
        ->and($user->can(Permission::ManageUsers->value))->toBeFalse()
        ->and($user->can(Permission::ManageRoles->value))->toBeFalse()
        ->and($user->can(Permission::ManageSettings->value))->toBeFalse();
});

it('marks both roles as requiring two-factor authentication', function () {
    expect(Role::twoFactorRequired())
        ->toContain(Role::SuperAdmin->value)
        ->toContain(Role::Moderator->value);
});

it('re-seeds idempotently', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(PermissionModel::count())->toBe(count(Permission::cases()))
        ->and(RoleModel::count())->toBe(count(Role::cases()));
});
