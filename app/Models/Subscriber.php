<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * Newsletter / alert subscriber (ТЗ §6.4.3). Double opt-in: created `pending`, confirmed via a
 * tokenized link. `token` also powers one-click unsubscribe.
 *
 * @property int $id
 * @property string $email
 * @property string $token
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

    /** @var list<string> */
    protected $fillable = [
        'email',
        'token',
        'locale',
        'status',
        'topics',
        'region_id',
        'confirmed_at',
        'consented_at',
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
