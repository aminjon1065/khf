<?php

use App\Enums\Role;
use App\Models\Language;
use App\Models\SiteGlobal;
use App\Models\User;
use App\Services\Cms\GlobalResolver;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
});

it('redirects guests from globals admin', function () {
    $this->get(route('admin.globals.index'))->assertRedirect(route('login'));
});

it('forbids a moderator from managing globals', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->get(route('admin.globals.index'))
        ->assertForbidden();
});

it('lists globals for a super admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.globals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/globals/index')
            ->has('globals', 4)
            ->where('globals.0.handle', 'president')
            ->where('globals.1.handle', 'social')
            ->where('globals.2.handle', 'footer')
            ->where('globals.3.handle', 'seo_defaults')
        );
});

it('shows the president global edit form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.globals.edit', 'president'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/globals/edit')
            ->where('global.handle', 'president')
            ->has('fields.url')
            ->has('fields.photo')
            ->has('blueprint')
        );
});

it('updates president global settings', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.globals.update', 'president'), [
            'fields' => [
                'url' => 'https://president.tj',
                'photo' => '/images/custom-president.webp',
            ],
        ])
        ->assertRedirect(route('admin.globals.edit', 'president'));

    $global = SiteGlobal::where('handle', 'president')->firstOrFail();
    $locale = Language::defaultCode();

    expect($global->fieldData($locale))->toBe([
        'url' => 'https://president.tj',
        'photo' => '/images/custom-president.webp',
    ]);

    app(GlobalResolver::class)->forget('president');

    expect(app(GlobalResolver::class)->president())->toBe([
        'url' => 'https://president.tj',
        'photo' => '/images/custom-president.webp',
    ]);
});

it('validates president global urls', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.globals.edit', 'president'))
        ->put(route('admin.globals.update', 'president'), [
            'fields' => [
                'url' => 'not-a-url',
                'photo' => '',
            ],
        ])
        ->assertSessionHasErrors(['fields.url', 'fields.photo']);
});

it('updates social global links and exposes them publicly', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.globals.update', 'social'), [
            'fields' => [
                'telegram' => 'https://t.me/kchs_tj',
                'facebook' => '',
                'instagram' => '',
                'youtube' => '',
                'x' => 'https://x.com/kchs_tj',
            ],
        ])
        ->assertRedirect(route('admin.globals.edit', 'social'));

    app(GlobalResolver::class)->forget('social');

    $this->get(route('subscriptions.create', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('socialLinks', 2)
            ->where('socialLinks.0.platform', 'telegram')
            ->where('socialLinks.1.platform', 'x'));
});

it('returns 404 for unknown global handles', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.globals.edit', 'unknown'))
        ->assertNotFound();
});

it('updates footer global settings and exposes them publicly', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.globals.update', 'footer'), [
            'fields' => [
                'government_url' => 'https://www.government.tj',
                'egov_url' => 'https://www.egov.tj',
                'hotline' => '112',
                'copyright' => 'Официальный портал',
                'resource_links' => [
                    ['label' => 'МЧС', 'url' => 'https://www.mchs.tj'],
                ],
            ],
        ])
        ->assertRedirect(route('admin.globals.edit', 'footer'));

    app(GlobalResolver::class)->forget('footer');

    expect(app(GlobalResolver::class)->footer())->toBe([
        'government_url' => 'https://www.government.tj',
        'egov_url' => 'https://www.egov.tj',
        'hotline' => '112',
        'copyright' => 'Официальный портал',
        'resource_links' => [
            ['label' => 'МЧС', 'url' => 'https://www.mchs.tj'],
        ],
    ]);

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('footerContent.government_url', 'https://www.government.tj')
            ->where('footerContent.hotline', '112')
            ->where('footerContent.copyright', 'Официальный портал')
            ->where('footerContent.resource_links.0.label', 'МЧС'));
});

it('updates seo defaults and uses them on pages without seo props', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.globals.update', 'seo_defaults'), [
            'fields' => [
                'title' => 'КЧС — портал',
                'description' => 'Официальный портал КЧС Таджикистана',
                'image' => '/images/emblem-ru.webp',
            ],
        ])
        ->assertRedirect(route('admin.globals.edit', 'seo_defaults'));

    app(GlobalResolver::class)->forget('seo_defaults');

    expect(app(GlobalResolver::class)->seoDefaults())->toMatchArray([
        'title' => 'КЧС — портал',
        'description' => 'Официальный портал КЧС Таджикистана',
    ]);

    $this->get(route('welcome', ['locale' => 'ru']))
        ->assertOk()
        ->assertSee('meta name="description" content="Официальный портал КЧС Таджикистана"', false);
});
