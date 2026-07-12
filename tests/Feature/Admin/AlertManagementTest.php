<?php

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTopic;
use App\Models\Alert;
use App\Models\Region;
use App\Models\Revision;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);

    $this->operator = User::factory()->withTwoFactor()->create();
    $this->operator->assignRole(Role::Moderator->value);
});

function alertPayload(array $overrides = []): array
{
    return array_merge([
        'hazard_level' => 'danger',
        'status' => 'published',
        'region_id' => null,
        'is_dismissible' => true,
        'starts_at' => null,
        'ends_at' => null,
        'translations' => [
            'tj' => ['title' => 'Огоҳӣ', 'body' => 'Матн'],
            'ru' => ['title' => 'Внимание', 'body' => 'Текст'],
            'en' => ['title' => '', 'body' => ''],
        ],
    ], $overrides);
}

it('forbids users without a CMS role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.alerts.index'))
        ->assertForbidden();
});

it('creates an alert with translations', function () {
    $this->actingAs($this->operator)
        ->post(route('admin.alerts.store'), alertPayload())
        ->assertRedirect(route('admin.content.index', 'alert'));

    $alert = Alert::with('translations')->first();

    expect($alert->hazard_level)->toBe(HazardLevel::Danger)
        ->and($alert->status)->toBe(AlertStatus::Published)
        ->and($alert->translations)->toHaveCount(2);

    expect(Revision::query()->where('revisionable_type', Alert::class)->count())->toBe(1);
});

it('creates a revision when an alert is updated', function () {
    $alert = Alert::factory()->create();
    $alert->upsertTranslations(['ru' => ['title' => 'Старое', 'body' => 'Текст']]);

    $this->actingAs($this->operator)
        ->put(route('admin.alerts.update', $alert), alertPayload([
            'translations' => [
                'tj' => ['title' => 'Нав', 'body' => 'Матн'],
                'ru' => ['title' => 'Новое', 'body' => 'Текст'],
                'en' => ['title' => '', 'body' => ''],
            ],
        ]))
        ->assertRedirect(route('admin.content.index', 'alert'));

    expect(Revision::query()->where('revisionable_type', Alert::class)->count())->toBe(1);
});

it('validates hazard level, status and default title', function () {
    $payload = alertPayload(['hazard_level' => 'x', 'status' => 'y']);
    $payload['translations']['tj']['title'] = '';

    $this->actingAs($this->operator)
        ->from(route('admin.alerts.create'))
        ->post(route('admin.alerts.store'), $payload)
        ->assertSessionHasErrors(['hazard_level', 'status', 'translations.tj.title']);
});

it('renders the list, create and trash screens', function () {
    $alert = Alert::factory()->create();
    $alert->upsertTranslations(['tj' => ['title' => 'Т']]);

    $this->actingAs($this->operator)->get(route('admin.alerts.index'))
        ->assertRedirect(route('admin.content.index', 'alert'));

    $this->actingAs($this->operator)->get(route('admin.content.index', 'alert'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'alert')
            ->has('entries.data', 1)
            ->where('entries.data.0.locales', ['tj']));

    $this->actingAs($this->operator)->get(route('admin.alerts.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/form')
            ->where('contentType.handle', 'alert')
            ->has('blueprint')
            ->has('fieldOptions.hazard_level', 4)
            ->has('urls.estimate'));

    $alert->delete();

    $this->actingAs($this->operator)->get(route('admin.alerts.trash'))
        ->assertRedirect(route('admin.content.index', ['type' => 'alert', 'trashed' => 1]));

    $this->actingAs($this->operator)->get(route('admin.content.index', ['type' => 'alert', 'trashed' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/content/index')
            ->where('contentType.handle', 'alert')
            ->where('filters.trashed', true)
            ->has('entries.data', 1));
});

it('only the active scope returns published, in-window alerts', function () {
    Alert::factory()->create(); // published, no window
    Alert::factory()->draft()->create();
    Alert::factory()->create(['ends_at' => now()->subDay()]); // expired

    expect(Alert::active()->count())->toBe(1);
});

it('shares active alerts with the front end ordered by severity', function () {
    $danger = Alert::factory()->create(['hazard_level' => HazardLevel::Danger]);
    $danger->upsertTranslations(['tj' => ['title' => 'Опасно']]);

    $critical = Alert::factory()->critical()->create();
    $critical->upsertTranslations(['tj' => ['title' => 'Критично']]);

    $this->get(route('welcome', ['locale' => 'tj']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('activeAlerts', 2)
            ->where('activeAlerts.0.title', 'Критично')
            ->where('activeAlerts.0.dismissible', false)
        );
});

it('soft deletes, restores and force deletes an alert', function () {
    $alert = Alert::factory()->create();
    $alert->upsertTranslations(['tj' => ['title' => 'Т']]);

    $this->actingAs($this->operator)->delete(route('admin.alerts.destroy', $alert));
    expect(Alert::count())->toBe(0)->and(Alert::onlyTrashed()->count())->toBe(1);

    $this->actingAs($this->operator)->patch(route('admin.alerts.restore', $alert));
    expect(Alert::count())->toBe(1);

    $this->actingAs($this->operator)->delete(route('admin.alerts.destroy', $alert));
    $this->actingAs($this->operator)->delete(route('admin.alerts.force-delete', $alert));
    expect(Alert::withTrashed()->count())->toBe(0);
});

it('estimates recipients for an alert', function () {
    $region = Region::factory()->create();

    Subscriber::factory()->count(3)->create([
        'status' => SubscriptionStatus::Confirmed,
        'topics' => [SubscriptionTopic::Alerts->value],
        'region_id' => $region->id,
    ]);

    Subscriber::factory()->count(2)->create([
        'status' => SubscriptionStatus::Confirmed,
        'topics' => [SubscriptionTopic::Alerts->value],
        'region_id' => null,
    ]);

    Subscriber::factory()->count(1)->create([
        'status' => SubscriptionStatus::Pending,
        'topics' => [SubscriptionTopic::Alerts->value],
        'region_id' => $region->id,
    ]);

    $response = $this->actingAs($this->operator)->getJson(route('admin.alerts.estimate', ['region_id' => $region->id]));

    $response->assertOk()
        ->assertJson(['count' => 5]); // 3 specific + 2 global
});
