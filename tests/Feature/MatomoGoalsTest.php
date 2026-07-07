<?php

use App\Support\Matomo;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);

    config([
        'matomo.url' => null,
        'matomo.site_id' => null,
        'matomo.goals' => [
            'appeal' => null,
            'tourist_group' => null,
            'subscription' => null,
        ],
    ]);
});

it('exposes matomo as disabled when analytics is not configured', function () {
    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('matomo.enabled', false)
            ->where('matomo.goals', []));
});

it('shares configured matomo goal ids with the front end', function () {
    config([
        'matomo.url' => 'https://analytics.example.com/',
        'matomo.site_id' => '3',
        'matomo.goals' => [
            'appeal' => '7',
            'tourist_group' => '8',
            'subscription' => '9',
        ],
    ]);

    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('matomo.enabled', true)
            ->where('matomo.goals.appeal', 7)
            ->where('matomo.goals.tourist_group', 8)
            ->where('matomo.goals.subscription', 9));
});

it('filters out empty goal ids', function () {
    config([
        'matomo.url' => 'https://analytics.example.com/',
        'matomo.site_id' => '3',
        'matomo.goals' => [
            'appeal' => '2',
            'tourist_group' => '',
            'subscription' => null,
        ],
    ]);

    expect(Matomo::goals())->toBe(['appeal' => 2]);
});

it('renders the matomo tracker snippet when analytics is configured', function () {
    config([
        'matomo.url' => 'https://analytics.example.com/',
        'matomo.site_id' => '5',
    ]);

    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertSee('analytics.example.com', false)
        ->assertSee("setSiteId', '5'", false);
});

it('does not render the matomo tracker snippet when analytics is disabled', function () {
    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertDontSee('matomo.js', false);
});
