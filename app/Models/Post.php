<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasPublishedVersion;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\ScopesPublicationWindow;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * News / press-release / announcement / operational-summary material (ТЗ §6.2). Multilingual
 * fields live in `post_translations`.
 *
 * @property int $id
 * @property PostType $type
 * @property int|null $category_id
 * @property int|null $author_id
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $unpublished_at
 */
class Post extends Model implements HasMedia
{
    use ClearsResponseCache;

    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasPublishedVersion;
    use HasRevisions;
    use HasSeoMeta;
    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;
    use ScopesPublicationWindow;
    use SoftDeletes;

    public const COVER_COLLECTION = 'cover';

    public const GALLERY_COLLECTION = 'gallery';

    public const ATTACHMENTS_COLLECTION = 'attachments';

    /** @var list<string> */
    protected $fillable = [
        'legacy_node_id',
        'type',
        'category_id',
        'author_id',
        'status',
        'published_at',
        'unpublished_at',
        'published_snapshot',
        'published_snapshot_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'published_snapshot' => 'array',
            'published_snapshot_at' => 'datetime',
        ];
    }

    /**
     * Single cover image (ТЗ §6.2). Conversions run via background queue.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COVER_COLLECTION)->singleFile();
        $this->addMediaCollection(self::GALLERY_COLLECTION);
        $this->addMediaCollection(self::ATTACHMENTS_COLLECTION);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 480, 320);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
