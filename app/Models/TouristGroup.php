<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use App\Enums\AppealStatus;
use App\Models\Concerns\GeneratesUniqueReference;
use Database\Factories\TouristGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
    use GeneratesUniqueReference;

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
            'leader_phone' => 'encrypted',
            'leader_email' => 'encrypted',
            'route' => 'encrypted',
            'equipment' => 'encrypted',
            'start_latitude' => EncryptedFloat::class,
            'start_longitude' => EncryptedFloat::class,
            'internal_note' => 'encrypted',
            'status' => AppealStatus::class,
        ];
    }

    protected static function referencePrefix(): string
    {
        return 'TUR';
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
