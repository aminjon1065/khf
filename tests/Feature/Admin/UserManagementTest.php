<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
});

it('redirects guests to login', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

it('forbids a moderator from managing users', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('lists users for a super admin with role options', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users.data')
            ->has('roles', count(Role::cases()))
        );
});

it('creates a user with a role', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'Нозим',
            'email' => 'nozim@kchs.tj',
            'password' => 'Sup3r-Secret-Pass!',
            'role' => Role::Moderator->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'nozim@kchs.tj')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole(Role::Moderator->value))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

it('validates a unique email and required password on create', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.store'), [
            'name' => 'Dup',
            'email' => $this->admin->email,
            'password' => '',
            'role' => Role::Moderator->value,
        ])
        ->assertSessionHasErrors(['email', 'password']);
});

it('updates a user and can reset the password', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Moderator->value);

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $user), [
            'name' => 'Updated',
            'email' => $user->email,
            'password' => 'Br4nd-New-Pass!',
            'role' => Role::Moderator->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->name)->toBe('Updated');
});

it('does not let an admin change their own role', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => Role::Moderator->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($this->admin->fresh()->hasRole(Role::SuperAdmin->value))->toBeTrue();
});

it('blocks and unblocks a user but not the acting admin', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Moderator->value);

    $this->actingAs($this->admin)->patch(route('admin.users.block', $user));
    expect($user->fresh()->isBlocked())->toBeTrue();

    $this->actingAs($this->admin)->patch(route('admin.users.block', $user));
    expect($user->fresh()->isBlocked())->toBeFalse();

    $this->actingAs($this->admin)->patch(route('admin.users.block', $this->admin));
    expect($this->admin->fresh()->isBlocked())->toBeFalse();
});

it('deletes a user but not the acting admin', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user));
    expect(User::find($user->id))->toBeNull();

    $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin));
    expect(User::find($this->admin->id))->not->toBeNull();
});

it('prevents a blocked user from authenticating', function () {
    $user = User::factory()->create(['blocked_at' => now()]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});
