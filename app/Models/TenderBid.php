<?php

namespace App\Models;

use App\Enums\AppealStatus;
use App\Models\Concerns\GeneratesUniqueReference;
use Database\Factories\TenderBidFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A bid submitted online for a {@see Tender} (ТЗ §9). Contains commercial data: access is
 * restricted to staff with permission (§12.5). `reference` is the public tracking number; the
 * uploaded bid document is stored on the private `local` disk. Reuses {@see AppealStatus} for the
 * processing lifecycle.
 *
 * @property int $id
 * @property string $reference
 * @property int $tender_id
 * @property string $company_name
 * @property string $contact_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $proposal
 * @property AppealStatus $status
 * @property int|null $assigned_to
 * @property string|null $internal_note
 */
class TenderBid extends Model implements HasMedia
{
    use GeneratesUniqueReference;

    /** @use HasFactory<TenderBidFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    public const DOCUMENT_COLLECTION = 'bid_document';

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'tender_id',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'proposal',
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
            'contact_name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'proposal' => 'encrypted',
            'internal_note' => 'encrypted',
        ];
    }

    protected static function referencePrefix(): string
    {
        return 'TND';
    }

    /**
     * Private disk → no public URL; the bid document is served through a permission-gated download
     * route (ТЗ §12.5 — protection of commercial data).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::DOCUMENT_COLLECTION)
            ->useDisk('local')
            ->singleFile();
    }

    /**
     * @return BelongsTo<Tender, $this>
     */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
