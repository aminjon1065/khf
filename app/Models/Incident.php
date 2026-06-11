<?php

namespace App\Models;

use App\Enums\HazardLevel;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\Concerns\HasTranslations;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

/**
 * An emergency event (ТЗ §6.3, §7.4). Multilingual title/description live in
 * `incident_translations`; geometry is a point (lat/lng).
 *
 * @property int $id
 * @property IncidentType $type
 * @property HazardLevel $hazard_level
 * @property IncidentStatus $status
 * @property int|null $region_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property Carbon|null $occurred_at
 */
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'type',
        'hazard_level',
        'status',
        'region_id',
        'latitude',
        'longitude',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IncidentType::class,
            'hazard_level' => HazardLevel::class,
            'status' => IncidentStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'occurred_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Not yet resolved (active or under control).
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [IncidentStatus::Active, IncidentStatus::Controlled]);
    }
}
