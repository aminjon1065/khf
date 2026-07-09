<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated cms users are redirected to the admin panel', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole(Role::Moderator->value);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('admin.dashboard'));
});

test('authenticated users without a cms role are redirected to profile settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('profile.edit'));
});
