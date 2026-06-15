<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('redirects guests to login', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('forbids authenticated users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('forces privileged users without 2FA to enable it first', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Moderator->value);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(route('security.edit'));
});

it('shows the CMS dashboard to a privileged user with confirmed 2FA', function () {
    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('stats.system.users')
            ->has('stats.system.languages')
            ->has('stats.system.roles')
            ->where('auth.roles', ['super-admin'])
        );
});
