<?php

namespace App\Models;

use App\Enums\AlertStatus;
use App\Enums\HazardLevel;
use App\Models\Concerns\HasTranslations;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Emergency alert / warning (ТЗ §6.4). Shown as the site banner on every public page when active.
 *
 * @property int $id
 * @property HazardLevel $hazard_level
 * @property AlertStatus $status
 * @property int|null $region_id
 * @property bool $is_dismissible
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'hazard_level',
        'status',
        'region_id',
        'is_dismissible',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hazard_level' => HazardLevel::class,
            'status' => AlertStatus::class,
            'is_dismissible' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Currently active alerts: published and within their (optional) time window (ТЗ §6.4.1).
     *
     * @param  Builder<Alert>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('status', AlertStatus::Published)
            ->where(fn (Builder $inner) => $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $inner) => $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
