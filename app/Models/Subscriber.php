<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * Newsletter / alert subscriber (ТЗ §6.4.3). Double opt-in: created `pending`, confirmed via a
 * tokenized link. `token` also powers one-click unsubscribe.
 *
 * @property int $id
 * @property string|null $email
 * @property string $token
 * @property string|null $push_token_hash
 * @property string $locale
 * @property SubscriptionStatus $status
 * @property list<string>|null $topics
 * @property int|null $region_id
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $consented_at
 */
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    use HasPushSubscriptions;

    // Provides notify()/notifyNow() for the web-push channel without the database-notifications
    // baggage of the full Notifiable trait (ТЗ §6.4.2).
    use RoutesNotifications;

    /** @var list<string> */
    protected $fillable = [
        'email',
        'token',
        'push_token_hash',
        'locale',
        'status',
        'topics',
        'region_id',
        'confirmed_at',
        'consented_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'token',
        'push_token_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'topics' => 'array',
            'confirmed_at' => 'datetime',
            'consented_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * @return array{plainText: string, hash: string}
     */
    public static function generatePushToken(): array
    {
        $plainText = Str::random(64);

        return [
            'plainText' => $plainText,
            'hash' => hash('sha256', $plainText),
        ];
    }

    public static function findByPushToken(string $plainText): ?self
    {
        return static::query()
            ->where('push_token_hash', hash('sha256', $plainText))
            ->first();
    }

    /**
     * @param  Builder<Subscriber>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', SubscriptionStatus::Confirmed);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
