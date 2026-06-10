<?php

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionConfirmation;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
});

function subscribeForm(array $overrides = []): array
{
    return array_merge([
        'email' => 'subscriber@example.com',
        'topics' => ['alerts', 'news'],
        'region_id' => null,
        'consent' => true,
        'website' => '',
    ], $overrides);
}

it('renders the subscription form', function () {
    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/subscribe/index')->has('topics', 3));
});

it('creates a pending subscriber and queues the confirmation email', function () {
    Mail::fake();

    $this->post(route('subscriptions.store', ['locale' => 'tj']), subscribeForm())
        ->assertRedirect(route('subscriptions.create', ['locale' => 'tj']))
        ->assertSessionHas('subscription_status', 'pending');

    $subscriber = Subscriber::first();

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->status)->toBe(SubscriptionStatus::Pending)
        ->and($subscriber->consented_at)->not->toBeNull();

    Mail::assertQueued(SubscriptionConfirmation::class);
});

it('requires consent and at least one topic', function () {
    $this->post(route('subscriptions.store', ['locale' => 'tj']), subscribeForm(['consent' => false, 'topics' => []]))
        ->assertSessionHasErrors(['consent', 'topics']);
});

it('rejects spam via the honeypot', function () {
    $this->post(route('subscriptions.store', ['locale' => 'tj']), subscribeForm(['website' => 'spam']))
        ->assertSessionHasErrors('website');

    expect(Subscriber::count())->toBe(0);
});

it('re-subscribing the same email does not duplicate', function () {
    Mail::fake();

    $this->post(route('subscriptions.store', ['locale' => 'tj']), subscribeForm());
    $this->post(route('subscriptions.store', ['locale' => 'tj']), subscribeForm());

    expect(Subscriber::where('email', 'subscriber@example.com')->count())->toBe(1);
});

it('confirms a subscription via the token link', function () {
    $subscriber = Subscriber::factory()->create();

    $this->get(route('subscriptions.confirm', ['locale' => 'tj', 'token' => $subscriber->token]))
        ->assertRedirect(route('subscriptions.create', ['locale' => 'tj']))
        ->assertSessionHas('subscription_status', 'confirmed');

    expect($subscriber->fresh()->status)->toBe(SubscriptionStatus::Confirmed);
});

it('unsubscribes via the token link', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();

    $this->get(route('subscriptions.unsubscribe', ['locale' => 'tj', 'token' => $subscriber->token]))
        ->assertSessionHas('subscription_status', 'unsubscribed');

    expect($subscriber->fresh()->status)->toBe(SubscriptionStatus::Unsubscribed);
});

it('restricts the CMS subscriber registry to staff with permission', function () {
    $this->get(route('admin.subscribers.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.subscribers.index'))
        ->assertForbidden();

    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);
    Subscriber::factory()->count(2)->create();

    $this->actingAs($moderator)->get(route('admin.subscribers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/subscribers/index')
            ->has('subscribers.data', 2)
            ->where('stats.total', 2)
        );
});
