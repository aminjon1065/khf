<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\GuideAudience;
use App\Enums\IncidentType;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\GuideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A safety guide / memo (ТЗ §6.5): how to act before, during and after a given hazard. Catalogued by
 * `hazard_type` (or general) and `audience`. Multilingual fields live in `guide_translations`;
 * optional downloads (printable PDFs) are stored on the private disk via medialibrary.
 *
 * @property int $id
 * @property IncidentType|null $hazard_type
 * @property GuideAudience $audience
 * @property ContentStatus $status
 * @property int $sort_order
 */
class Guide extends Model implements HasMedia
{
    use ClearsResponseCache;

    /** @use HasFactory<GuideFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public const FILES_COLLECTION = 'files';

    /** @var list<string> */
    protected $fillable = [
        'hazard_type',
        'audience',
        'status',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hazard_type' => IncidentType::class,
            'audience' => GuideAudience::class,
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILES_COLLECTION)->useDisk('local');
    }

    /**
     * @param  Builder<Guide>  $query
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
