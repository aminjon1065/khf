<?php

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Enums\Role;
use App\Jobs\SendAlertNotifications;
use App\Listeners\RecordNotificationDelivery;
use App\Mail\AlertNotification;
use App\Models\Alert;
use App\Models\NotificationLog;
use App\Models\Region;
use App\Models\Subscriber;
use App\Models\User;
use App\Notifications\AlertPushNotification;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

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

it('is idempotent across a retry — no subscriber is emailed twice', function () {
    Mail::fake();

    $alert = Alert::factory()->create(['status' => AlertStatus::Published, 'region_id' => null]);
    $alert->upsertTranslations(['tj' => ['title' => 'Тревога', 'body' => 'Текст']]);
    Subscriber::factory()->confirmed()->create(['topics' => ['alerts']]);

    (new SendAlertNotifications($alert->id))->handle();

    // Simulate a queue retry after a partial failure: the completion guard is cleared but the
    // per-recipient log survives, so the second run must resume without re-blasting anyone.
    $alert->forceFill(['notified_at' => null])->save();
    (new SendAlertNotifications($alert->id))->handle();

    Mail::assertQueued(AlertNotification::class, 1);
    expect(NotificationLog::where('channel', 'email')->count())->toBe(1);
});

it('queues alert delivery on the priority alerts queue', function () {
    Mail::fake();

    $alert = Alert::factory()->create(['status' => AlertStatus::Published]);
    $alert->upsertTranslations(['tj' => ['title' => 'Тревога', 'body' => 'Текст']]);
    Subscriber::factory()->confirmed()->create(['topics' => ['alerts']]);

    (new SendAlertNotifications($alert->id))->handle();

    Mail::assertQueued(AlertNotification::class, fn (AlertNotification $mail) => $mail->queue === 'alerts');
});

it('deep-links web push to the alert detail page, not the homepage', function () {
    Notification::fake();
    Mail::fake();

    $alert = Alert::factory()->create(['status' => AlertStatus::Published]);
    $alert->upsertTranslations(['tj' => ['title' => 'Тревога', 'body' => 'Текст']]);

    $subscriber = Subscriber::factory()->confirmed()->create(['topics' => ['alerts'], 'locale' => 'tj']);
    $subscriber->updatePushSubscription('https://fcm.googleapis.com/fcm/send/test');

    (new SendAlertNotifications($alert->id))->handle();

    Notification::assertSentTo(
        $subscriber,
        AlertPushNotification::class,
        fn (AlertPushNotification $notification) => str_contains($notification->url, "/alerts/{$alert->id}"),
    );
});

it('records real email delivery status in the notification log', function () {
    $alert = Alert::factory()->create(['status' => AlertStatus::Published]);
    $alert->upsertTranslations(['tj' => ['title' => 'Тревога', 'body' => 'Текст']]);
    Subscriber::factory()->confirmed()->create(['topics' => ['alerts'], 'locale' => 'tj']);

    // No Mail::fake — the sync queue + array transport actually "send", firing MessageSent so the
    // delivery listener flips the row from queued to sent.
    (new SendAlertNotifications($alert->id))->handle();

    expect(NotificationLog::where('channel', 'email')->first()->status)->toBe('sent');
});

it('marks the log failed with an error when a push channel throws', function () {
    $alert = Alert::factory()->create(['status' => AlertStatus::Published]);
    $subscriber = Subscriber::factory()->confirmed()->create(['topics' => ['alerts']]);

    $log = NotificationLog::create([
        'alert_id' => $alert->id,
        'subscriber_id' => $subscriber->id,
        'channel' => 'webpush',
        'status' => 'queued',
        'sent_at' => now(),
    ]);

    $listener = new RecordNotificationDelivery;
    $notification = new AlertPushNotification('t', 'b', 'https://example.test/tj/alerts/1', 'critical', $log->id);
    $listener->handleNotificationFailed(new NotificationFailed($subscriber, $notification, 'webpush', [
        'exception' => new RuntimeException('endpoint gone'),
    ]));

    $log->refresh();
    expect($log->status)->toBe('failed')
        ->and($log->error)->toContain('endpoint gone');
});

it('marks the log sent when a push channel succeeds', function () {
    $alert = Alert::factory()->create(['status' => AlertStatus::Published]);
    $subscriber = Subscriber::factory()->confirmed()->create(['topics' => ['alerts']]);

    $log = NotificationLog::create([
        'alert_id' => $alert->id,
        'subscriber_id' => $subscriber->id,
        'channel' => 'webpush',
        'status' => 'queued',
        'sent_at' => now(),
    ]);

    $listener = new RecordNotificationDelivery;
    $notification = new AlertPushNotification('t', 'b', 'https://example.test/tj/alerts/1', 'critical', $log->id);
    $listener->handleNotificationSent(new NotificationSent($subscriber, $notification, 'webpush'));

    expect($log->refresh()->status)->toBe('sent');
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
