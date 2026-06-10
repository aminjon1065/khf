<?php

namespace App\Jobs;

use App\Enums\AlertStatus;
use App\Enums\SubscriptionTopic;
use App\Mail\AlertNotification;
use App\Models\Alert;
use App\Models\NotificationLog;
use App\Models\Subscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Fan-out an emergency alert to confirmed e-mail subscribers matching its topic and region
 * (ТЗ §6.4.3). Idempotent: marks the alert `notified_at` and bails out if already sent
 * (double-send protection, §6.4.4). Runs on the cron-driven queue (D-10).
 */
class SendAlertNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $alertId) {}

    public function handle(): void
    {
        $alert = Alert::query()->with('translations')->find($this->alertId);

        if ($alert === null || $alert->status !== AlertStatus::Published || $alert->notified_at !== null) {
            return;
        }

        Subscriber::confirmed()
            ->whereJsonContains('topics', SubscriptionTopic::Alerts->value)
            ->when($alert->region_id !== null, fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner->whereNull('region_id')->orWhere('region_id', $alert->region_id),
            ))
            ->chunkById(200, function ($subscribers) use ($alert): void {
                foreach ($subscribers as $subscriber) {
                    $translation = $alert->translation($subscriber->locale);

                    Mail::to($subscriber->email)->queue(new AlertNotification(
                        $translation?->title ?? '',
                        $translation?->body ?? '',
                        $alert->hazard_level->label(),
                        route('subscriptions.unsubscribe', ['locale' => $subscriber->locale, 'token' => $subscriber->token]),
                    ));

                    NotificationLog::create([
                        'alert_id' => $alert->id,
                        'subscriber_id' => $subscriber->id,
                        'channel' => 'email',
                        'status' => 'queued',
                        'sent_at' => now(),
                    ]);
                }
            });

        $alert->forceFill(['notified_at' => now()])->save();
    }
}
