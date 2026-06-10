<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Delivery log for outbound notifications (ТЗ §6.4 — учёт статусов отправки и ошибок).
 *
 * @property int $id
 * @property int|null $alert_id
 * @property int|null $subscriber_id
 * @property string $channel
 * @property string $status
 * @property string|null $error
 * @property Carbon|null $sent_at
 */
class NotificationLog extends Model
{
    protected $table = 'notifications_log';

    /** @var list<string> */
    protected $fillable = [
        'alert_id',
        'subscriber_id',
        'channel',
        'status',
        'error',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Subscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    /**
     * @return BelongsTo<Alert, $this>
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }
}
