<?php

namespace App\Models;

use App\Enums\AppealStatus;
use Database\Factories\TouristGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Tourist-group / route registration (ТЗ §6.6). Contains personal data: access is restricted to the
 * moderator role (§12.5). `reference` is the public tracking number. Reuses {@see AppealStatus} for
 * the processing lifecycle.
 *
 * @property int $id
 * @property string $reference
 * @property string $leader_name
 * @property string $leader_phone
 * @property string|null $leader_email
 * @property int $participants_count
 * @property string $route
 * @property string|null $equipment
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int|null $region_id
 * @property float|null $start_latitude
 * @property float|null $start_longitude
 * @property AppealStatus $status
 * @property int|null $assigned_to
 * @property string|null $internal_note
 */
class TouristGroup extends Model
{
    /** @use HasFactory<TouristGroupFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'leader_name',
        'leader_phone',
        'leader_email',
        'participants_count',
        'route',
        'equipment',
        'start_date',
        'end_date',
        'region_id',
        'start_latitude',
        'start_longitude',
        'status',
        'assigned_to',
        'internal_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'participants_count' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'start_latitude' => 'float',
            'start_longitude' => 'float',
            'status' => AppealStatus::class,
        ];
    }

    /**
     * Generate a unique public tracking reference, e.g. TUR-2026-AB12CD.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'TUR-'.now()->year.'-'.Str::upper(Str::random(6));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
