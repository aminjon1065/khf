<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\GalleryFactory;
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
 * A photo gallery / album (ТЗ §20 подпункт «ш»). Multilingual title/description live in
 * `gallery_translations`; photos are media items on the public disk.
 *
 * @property int $id
 * @property ContentStatus $status
 * @property int $sort_order
 */
class Gallery extends Model implements HasMedia
{
    use ClearsResponseCache;

    /** @use HasFactory<GalleryFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;

    public const PHOTOS_COLLECTION = 'photos';

    /** @var list<string> */
    protected $fillable = [
        'status',
        'sort_order',
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
        $this->addMediaCollection(self::PHOTOS_COLLECTION);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 600, 400);
    }

    /**
     * @param  Builder<Gallery>  $query
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
