<?php

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->moderator = User::factory()->withTwoFactor()->create();
    $this->moderator->assignRole(Role::Moderator->value);
});

it('redirects guests to login', function () {
    $this->get(route('admin.subscribers.index'))->assertRedirect(route('login'));
});

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.subscribers.index'))
        ->assertForbidden();
});

it('renders the subscriber registry with stats', function () {
    Subscriber::factory()->count(2)->create();
    Subscriber::factory()->confirmed()->create();

    $this->actingAs($this->moderator)
        ->get(route('admin.subscribers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/subscribers/index')
            ->has('subscribers.data', 3)
            ->where('stats.total', 3)
            ->where('stats.confirmed', 1)
            ->where('stats.pending', 2)
        );
});

it('filters subscribers by status', function () {
    Subscriber::factory()->create(['status' => SubscriptionStatus::Pending]);
    Subscriber::factory()->confirmed()->create();

    $this->actingAs($this->moderator)
        ->get(route('admin.subscribers.index', ['status' => SubscriptionStatus::Confirmed->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscribers.data', 1)
            ->where('filters.status', SubscriptionStatus::Confirmed->value)
        );
});

it('deletes a subscriber', function () {
    $subscriber = Subscriber::factory()->create();

    $this->actingAs($this->moderator)
        ->delete(route('admin.subscribers.destroy', $subscriber))
        ->assertRedirect(route('admin.subscribers.index'));

    expect(Subscriber::query()->find($subscriber->id))->toBeNull();
});
