<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasTranslations;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A registry document (ТЗ §6.8). Files are stored on the private `local` disk and served through a
 * controlled download route (§12.4 — outside the public webroot). Name/description are per-locale.
 *
 * @property int $id
 * @property DocumentType $type
 * @property string|null $source
 * @property Carbon|null $document_date
 * @property ContentStatus $status
 * @property int $sort_order
 */
class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use HasRevisions;
    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public const FILES_COLLECTION = 'files';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'source',
        'document_date',
        'status',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'document_date' => 'date',
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        // Private disk → no public URL; downloads go through a controlled route (ТЗ §12.4).
        $this->addMediaCollection(self::FILES_COLLECTION)->useDisk('local');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @param  Builder<Document>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
