<?php

use App\Services\Cms\GlobalResolver;
use App\Support\SocialLinks;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);

    config([
        'cms.globals.social.fallback' => [
            'telegram' => null,
            'facebook' => null,
            'instagram' => null,
            'youtube' => null,
            'x' => null,
        ],
    ]);

    app(GlobalResolver::class)->forget('social');
});

it('shares an empty social link list when no profiles are configured', function () {
    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('socialLinks', []));
});

it('shares only valid https social profile urls', function () {
    config([
        'cms.globals.social.fallback' => [
            'telegram' => 'https://t.me/kchs_tj',
            'facebook' => 'not-a-url',
            'instagram' => '',
            'youtube' => 'javascript:alert(1)',
            'x' => 'https://x.com/kchs_tj',
        ],
    ]);

    app(GlobalResolver::class)->forget('social');

    expect(SocialLinks::all())->toBe([
        ['platform' => 'telegram', 'url' => 'https://t.me/kchs_tj'],
        ['platform' => 'x', 'url' => 'https://x.com/kchs_tj'],
    ]);

    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('socialLinks', 2)
            ->where('socialLinks.0.platform', 'telegram')
            ->where('socialLinks.1.platform', 'x'));
});

it('passes configured social links to the public layout', function () {
    config([
        'cms.globals.social.fallback.telegram' => 'https://t.me/kchs_tj',
    ]);

    app(GlobalResolver::class)->forget('social');

    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('socialLinks', 1)
            ->where('socialLinks.0.url', 'https://t.me/kchs_tj'));
});

it('hides social links when no profiles are configured', function () {
    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('socialLinks', []));
});
