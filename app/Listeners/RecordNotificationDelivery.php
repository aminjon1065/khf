<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use App\Notifications\AlertPushNotification;
use Illuminate\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Turn the {@see NotificationLog} into a real delivery journal (ТЗ §6.4.4 — учёт статусов отправки
 * и ошибок). The fan-out job records each recipient+channel as `queued`; these listeners flip the
 * row to `sent` when the message actually leaves the worker, or to `failed` (with the error) when a
 * channel throws. E-mail rows are correlated by the `X-KCHS-Log-Id` header the mailable stamps;
 * web-push rows by the log id carried on the notification.
 */
class RecordNotificationDelivery
{
    public function handleMessageSent(MessageSent $event): void
    {
        $header = $event->message->getHeaders()->get('X-KCHS-Log-Id');

        if ($header === null) {
            return;
        }

        $this->markSent((int) $header->getBodyAsString());
    }

    public function handleNotificationSent(NotificationSent $event): void
    {
        if ($event->notification instanceof AlertPushNotification && $event->notification->logId !== null) {
            $this->markSent($event->notification->logId);
        }
    }

    public function handleNotificationFailed(NotificationFailed $event): void
    {
        if (! $event->notification instanceof AlertPushNotification || $event->notification->logId === null) {
            return;
        }

        NotificationLog::whereKey($event->notification->logId)->update([
            'status' => 'failed',
            'error' => $this->describe($event->data),
        ]);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MessageSent::class, [self::class, 'handleMessageSent']);
        $events->listen(NotificationSent::class, [self::class, 'handleNotificationSent']);
        $events->listen(NotificationFailed::class, [self::class, 'handleNotificationFailed']);
    }

    private function markSent(int $logId): void
    {
        NotificationLog::whereKey($logId)->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function describe(array $data): string
    {
        $reason = $data['exception'] ?? $data['report'] ?? null;

        if ($reason instanceof \Throwable) {
            return mb_substr($reason->getMessage(), 0, 500);
        }

        return mb_substr(is_string($reason) ? $reason : 'delivery failed', 0, 500);
    }
}
