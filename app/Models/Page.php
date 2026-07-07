<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasTranslations;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A static content page (ТЗ §6, §7.2). Multilingual fields live in `page_translations`.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property ContentStatus $status
 * @property int $sort_order
 * @property bool $is_home
 */
class Page extends Model implements HasMedia
{
    use ClearsResponseCache;

    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use HasRevisions;
    use HasSeoMeta;
    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    public const COVER_COLLECTION = 'cover';

    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'status',
        'sort_order',
        'is_home',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
            'is_home' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COVER_COLLECTION)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 1200, 630);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
