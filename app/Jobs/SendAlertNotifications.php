<?php

namespace App\Jobs;

use App\Enums\AlertStatus;
use App\Enums\SubscriptionTopic;
use App\Mail\AlertNotification;
use App\Models\Alert;
use App\Models\NotificationLog;
use App\Models\Subscriber;
use App\Notifications\AlertPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Fan-out an emergency alert to confirmed e-mail + web-push subscribers matching its topic and
 * region (ТЗ §6.4.3). Runs on the dedicated `alerts` queue so delivery preempts bulk digests and
 * image conversions (ТЗ §13, §6.4).
 *
 * Idempotent at two levels (ТЗ §6.4.4): the alert-level `notified_at` guard stops a second manual
 * dispatch, and a per-recipient `NotificationLog(alert_id, subscriber_id, channel)` unique key means
 * a queue retry after a partial failure resumes instead of re-blasting everyone already reached.
 */
class SendAlertNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $alertId)
    {
        $this->onQueue('alerts');
    }

    public function handle(): void
    {
        $alert = Alert::query()->with('translations')->find($this->alertId);

        // `notified_at` guards against a second *dispatch* (e.g. re-editing a published alert). A
        // queue *retry* of the same job leaves `notified_at` null until the run completes, so it
        // resumes and the per-recipient log skips whoever was already reached (ТЗ §6.4.4).
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
                    $this->deliver($alert, $subscriber);
                }
            });

        $alert->forceFill(['notified_at' => now()])->save();
    }

    /**
     * Deliver both channels to one subscriber, skipping any channel already logged for this alert.
     */
    private function deliver(Alert $alert, Subscriber $subscriber): void
    {
        $translation = $alert->translation($subscriber->locale);
        $title = $translation?->title ?? '';
        $body = $translation?->body ?? '';
        $alertUrl = route('alerts.show', ['locale' => $subscriber->locale, 'alert' => $alert->id]);

        if (! empty($subscriber->email)) {
            $log = $this->claim($alert, $subscriber, 'email');

            if ($log !== null) {
                Mail::to($subscriber->email)->locale($subscriber->locale)->queue(
                    (new AlertNotification(
                        $title,
                        $body,
                        $alert->hazard_level,
                        route('subscriptions.unsubscribe', ['locale' => $subscriber->locale, 'token' => $subscriber->token]),
                        $alertUrl,
                        $log->id,
                    ))->onQueue('alerts'),
                );
            }
        }

        if ($subscriber->pushSubscriptions()->exists()) {
            $log = $this->claim($alert, $subscriber, 'webpush');

            if ($log !== null) {
                $subscriber->notify(new AlertPushNotification(
                    $title,
                    $body,
                    $alertUrl,
                    $alert->hazard_level->value,
                    $log->id,
                ));
            }
        }
    }

    /**
     * Atomically reserve a delivery slot for this recipient+channel. Returns the fresh log row, or
     * null when this alert was already dispatched to the recipient on that channel (retry / re-run).
     */
    private function claim(Alert $alert, Subscriber $subscriber, string $channel): ?NotificationLog
    {
        $log = NotificationLog::firstOrCreate(
            [
                'alert_id' => $alert->id,
                'subscriber_id' => $subscriber->id,
                'channel' => $channel,
            ],
            [
                'status' => 'queued',
                'sent_at' => now(),
            ],
        );

        return $log->wasRecentlyCreated ? $log : null;
    }
}
