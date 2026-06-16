<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\LeaderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A member of the agency leadership (ТЗ §20 подпункт «г»). Multilingual fields live in
 * `leader_translations`; the portrait is a single media item on the public disk.
 *
 * @property int $id
 * @property ContentStatus $status
 * @property int $sort_order
 * @property string|null $email
 * @property string|null $phone
 */
class Leader extends Model implements HasMedia
{
    use ClearsResponseCache;

    /** @use HasFactory<LeaderFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;

    public const PHOTO_COLLECTION = 'photo';

    /** @var list<string> */
    protected $fillable = [
        'status',
        'sort_order',
        'email',
        'phone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTO_COLLECTION)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 400);
    }

    /**
     * @param  Builder<Leader>  $query
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
