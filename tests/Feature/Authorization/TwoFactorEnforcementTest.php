<?php

use App\Enums\Role;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    Route::middleware(['web', 'auth', EnsureTwoFactorEnabled::class])
        ->get('/__test/protected', fn () => 'passed')
        ->name('test.protected');
});

it('redirects a privileged user without 2FA to the security settings', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Moderator->value);

    $this->actingAs($user)
        ->get('/__test/protected')
        ->assertRedirect(route('security.edit'));
});

it('allows a privileged user with confirmed 2FA through', function () {
    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)
        ->get('/__test/protected')
        ->assertOk()
        ->assertSee('passed');
});

it('does not block a user without a privileged role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/__test/protected')
        ->assertOk();
});

it('skips enforcement when two factor is disabled for the environment', function () {
    config(['fortify.require_two_factor' => false]);

    $user = User::factory()->create();
    $user->assignRole(Role::Moderator->value);

    $this->actingAs($user)
        ->get('/__test/protected')
        ->assertOk();
});
