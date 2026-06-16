<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\TenderType;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasTranslations;
use Database\Factories\TenderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Public-procurement tender (ТЗ §9, §20 подпункт «э» — «торговая площадка»). Multilingual fields
 * live in `tender_translations`; companies submit bids online via {@see TenderBid}.
 *
 * @property int $id
 * @property string|null $tender_number
 * @property ContentStatus $status
 * @property TenderType $type
 * @property string|null $budget
 * @property int $lots_count
 * @property Carbon|null $published_at
 * @property Carbon|null $deadline_at
 * @property int|null $created_by
 */
class Tender extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<TenderFactory> */
    use HasFactory;

    use HasSeoMeta;
    use HasTranslations;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'tender_number',
        'status',
        'type',
        'budget',
        'lots_count',
        'published_at',
        'deadline_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'type' => TenderType::class,
            'budget' => 'decimal:2',
            'lots_count' => 'integer',
            'published_at' => 'datetime',
            'deadline_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TenderBid, $this>
     */
    public function bids(): HasMany
    {
        return $this->hasMany(TenderBid::class);
    }

    /**
     * Published and past their publish time (ТЗ §20 «э» — scheduled publishing).
     *
     * @param  Builder<Tender>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published)
            ->where(function (Builder $inner) {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Published tenders still accepting bids (deadline today or later, or open-ended).
     *
     * @param  Builder<Tender>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->published()->where(function (Builder $inner) {
            $inner->whereNull('deadline_at')->orWhereDate('deadline_at', '>=', now());
        });
    }

    /**
     * Whether the bid submission deadline (ТЗ §9) has not yet passed.
     */
    public function isOpen(): bool
    {
        return $this->deadline_at === null || $this->deadline_at->endOfDay()->isFuture();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
