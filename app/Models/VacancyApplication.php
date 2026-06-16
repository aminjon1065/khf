<?php

namespace App\Models;

use App\Enums\AppealStatus;
use Database\Factories\VacancyApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Online application (questionnaire) submitted for a {@see Vacancy} (ТЗ §21). Contains personal
 * data: access is restricted to staff with permission (§12.5). `reference` is the public tracking
 * number; the uploaded questionnaire/CV is stored on the private `local` disk. Reuses
 * {@see AppealStatus} for the processing lifecycle.
 *
 * @property int $id
 * @property string $reference
 * @property int $vacancy_id
 * @property string $full_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $cover_letter
 * @property AppealStatus $status
 * @property int|null $assigned_to
 * @property string|null $internal_note
 */
class VacancyApplication extends Model implements HasMedia
{
    /** @use HasFactory<VacancyApplicationFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    public const RESUME_COLLECTION = 'resume';

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'vacancy_id',
        'full_name',
        'email',
        'phone',
        'cover_letter',
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
            'status' => AppealStatus::class,
        ];
    }

    /**
     * Generate a unique public tracking reference, e.g. VAC-2026-AB12CD.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'VAC-'.now()->year.'-'.Str::upper(Str::random(6));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Private disk → no public URL; the questionnaire/CV is served through a permission-gated
     * download route (ТЗ §12.5 — personal-data protection).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::RESUME_COLLECTION)
            ->useDisk('local')
            ->singleFile();
    }

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
