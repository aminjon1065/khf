<?php

namespace App\Models;

use App\Enums\AppealCategory;
use App\Enums\AppealStatus;
use Database\Factories\AppealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Citizen appeal — electronic reception (ТЗ §6.7). Contains personal data: access is restricted to
 * the moderator role (§12.5). `reference` is the public tracking number.
 *
 * @property int $id
 * @property string $reference
 * @property AppealCategory $category
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $subject
 * @property string $message
 * @property AppealStatus $status
 * @property int|null $assigned_to
 * @property string|null $internal_note
 * @property Carbon|null $deadline_at
 */
class Appeal extends Model implements HasMedia
{
    /** @use HasFactory<AppealFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    public const ATTACHMENTS_COLLECTION = 'attachments';

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'category',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'assigned_to',
        'internal_note',
        'deadline_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AppealCategory::class,
            'status' => AppealStatus::class,
            'deadline_at' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ATTACHMENTS_COLLECTION)
            ->useDisk('local');
    }

    /**
     * Generate a unique public tracking reference, e.g. OBR-2026-AB12CD.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'OBR-'.now()->year.'-'.Str::upper(Str::random(6));
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
}
