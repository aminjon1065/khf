<?php

use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Enums\Role;
use App\Models\Guide;
use App\Models\GuideTranslation;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->editor = User::factory()->withTwoFactor()->create();
    $this->editor->assignRole(Role::Moderator->value);
});

function guidePayload(array $overrides = []): array
{
    return array_merge([
        'hazard_type' => 'earthquake',
        'audience' => 'general',
        'status' => 'published',
        'sort_order' => 0,
        'translations' => [
            'tj' => ['title' => 'Заминҷунбӣ', 'summary' => 'Хулоса', 'content' => '<p>Матн</p>'],
            'ru' => ['title' => 'Землетрясение', 'summary' => 'Сводка', 'content' => '<p>Текст</p>'],
            'en' => ['title' => '', 'summary' => '', 'content' => ''],
        ],
    ], $overrides);
}

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.guides.index'))
        ->assertForbidden();
});

it('creates a guide with hazard type, audience and sanitised content', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.guides.store'), guidePayload([
            'translations' => [
                'tj' => ['title' => 'Заминҷунбӣ', 'summary' => 'с', 'content' => '<p>ok</p><script>alert(1)</script>'],
                'ru' => ['title' => 'Землетрясение', 'summary' => 'с', 'content' => '<p>ok</p>'],
            ],
        ]))
        ->assertRedirect(route('admin.guides.index'));

    $guide = Guide::with('translations')->first();

    expect($guide->hazard_type)->toBe(IncidentType::Earthquake)
        ->and($guide->audience)->toBe(GuideAudience::General)
        ->and($guide->translations)->toHaveCount(2)
        ->and($guide->translation('tj')->content)->not->toContain('<script>');
});

it('allows a general guide with no hazard binding', function () {
    $this->actingAs($this->editor)
        ->post(route('admin.guides.store'), guidePayload(['hazard_type' => null]))
        ->assertRedirect(route('admin.guides.index'));

    expect(Guide::first()->hazard_type)->toBeNull();
});

it('validates the default-locale title', function () {
    $payload = guidePayload();
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->editor)
        ->from(route('admin.guides.create'))
        ->post(route('admin.guides.store'), $payload)
        ->assertSessionHasErrors('translations.tj.title');
});

it('renders the index with translation-status locales', function () {
    $guide = Guide::factory()->create();
    $guide->upsertTranslations(['tj' => ['title' => 'Т', 'slug' => 't-tj']]);

    $this->actingAs($this->editor)->get(route('admin.guides.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/guides/index')
            ->has('guides.data', 1)
            ->where('guides.data.0.locales', ['tj']));
});

it('renders the admin create and edit screens', function () {
    $guide = Guide::factory()->create();
    $guide->upsertTranslations(['tj' => ['title' => 'Т', 'slug' => 't-edit']]);

    $this->actingAs($this->editor)->get(route('admin.guides.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/guides/form')
            ->has('blueprint')
            ->has('fieldOptions')
            ->has('statuses')
            ->has('locales'));

    $this->actingAs($this->editor)->get(route('admin.guides.edit', $guide))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/guides/form')
            ->where('guide.id', $guide->id)
            ->has('blueprint.sections.sidebar.fields'));
});

it('generates unique slugs when auto-generated slugs would collide', function () {
    // Tajik titles that Str::slug strips to empty would otherwise collapse to the same slug.
    $payload = guidePayload([
        'translations' => [
            'tj' => ['title' => 'Ҳ', 'summary' => 'с', 'content' => '<p>a</p>'],
            'ru' => ['title' => 'А', 'summary' => 'с', 'content' => '<p>a</p>'],
        ],
    ]);

    $this->actingAs($this->editor)->post(route('admin.guides.store'), $payload)->assertRedirect();
    $this->actingAs($this->editor)->post(route('admin.guides.store'), $payload)->assertRedirect();

    $slugs = GuideTranslation::where('locale', 'tj')->pluck('slug');

    expect($slugs)->toHaveCount(2)
        ->and($slugs->unique())->toHaveCount(2);
});

it('soft deletes, restores and force deletes a guide', function () {
    $guide = Guide::factory()->create();
    $guide->upsertTranslations(['tj' => ['title' => 'Т', 'slug' => 'g-tj']]);

    $this->actingAs($this->editor)->delete(route('admin.guides.destroy', $guide));
    expect(Guide::count())->toBe(0)->and(Guide::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->editor)->patch(route('admin.guides.restore', $guide));
    expect(Guide::count())->toBe(1);

    $this->actingAs($this->editor)->delete(route('admin.guides.destroy', $guide));
    $this->actingAs($this->editor)->delete(route('admin.guides.force-delete', $guide));
    expect(Guide::withTrashed()->count())->toBe(0);
});
