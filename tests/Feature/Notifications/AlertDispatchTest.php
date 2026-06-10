<?php

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Enums\Role;
use App\Jobs\SendAlertNotifications;
use App\Mail\AlertNotification;
use App\Models\Alert;
use App\Models\NotificationLog;
use App\Models\Region;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LanguageSeeder::class);
    $this->seed(RegionSeeder::class);
});

function publishedAlertPayload(array $overrides = []): array
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

it('dispatches the notification job when a published alert is created', function () {
    Bus::fake();

    $operator = User::factory()->withTwoFactor()->create();
    $operator->assignRole(Role::Moderator->value);

    $this->actingAs($operator)->post(route('admin.alerts.store'), publishedAlertPayload());

    Bus::assertDispatched(SendAlertNotifications::class);
});

it('does not dispatch for a draft alert', function () {
    Bus::fake();

    $operator = User::factory()->withTwoFactor()->create();
    $operator->assignRole(Role::Moderator->value);

    $this->actingAs($operator)->post(route('admin.alerts.store'), publishedAlertPayload(['status' => 'draft']));

    Bus::assertNotDispatched(SendAlertNotifications::class);
});

it('emails confirmed subscribers on the alerts topic and logs delivery', function () {
    Mail::fake();

    $alert = Alert::factory()->create(['status' => AlertStatus::Published, 'region_id' => null]);
    $alert->upsertTranslations(['tj' => ['title' => 'Тревога', 'body' => 'Текст']]);

    Subscriber::factory()->confirmed()->create(['topics' => ['alerts']]);
    Subscriber::factory()->create(['topics' => ['alerts']]); // pending — skipped
    Subscriber::factory()->confirmed()->create(['topics' => ['news']]); // wrong topic — skipped

    (new SendAlertNotifications($alert->id))->handle();

    Mail::assertQueued(AlertNotification::class, 1);
    expect(NotificationLog::count())->toBe(1)
        ->and($alert->fresh()->notified_at)->not->toBeNull();
});

it('does not re-send a previously notified alert', function () {
    Mail::fake();

    $alert = Alert::factory()->create(['status' => AlertStatus::Published, 'notified_at' => now()]);
    Subscriber::factory()->confirmed()->create(['topics' => ['alerts']]);

    (new SendAlertNotifications($alert->id))->handle();

    Mail::assertNothingQueued();
});

it('targets subscribers in the alert region or with no region', function () {
    Mail::fake();

    $dushanbe = Region::where('code', 'DUSHANBE')->firstOrFail();
    $sughd = Region::where('code', 'SUGHD')->firstOrFail();

    $alert = Alert::factory()->create([
        'status' => AlertStatus::Published,
        'hazard_level' => HazardLevel::Critical,
        'region_id' => $dushanbe->id,
    ]);
    $alert->upsertTranslations(['tj' => ['title' => 'Локально', 'body' => '']]);

    Subscriber::factory()->confirmed()->create(['topics' => ['alerts'], 'region_id' => $dushanbe->id]); // match
    Subscriber::factory()->confirmed()->create(['topics' => ['alerts'], 'region_id' => null]); // all regions
    Subscriber::factory()->confirmed()->create(['topics' => ['alerts'], 'region_id' => $sughd->id]); // other region — skipped

    (new SendAlertNotifications($alert->id))->handle();

    Mail::assertQueued(AlertNotification::class, 2);
});
