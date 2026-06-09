<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Models\Concerns\HasTranslations;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
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
 */
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    public const COVER_COLLECTION = 'cover';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'category_id',
        'author_id',
        'status',
        'published_at',
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
        ];
    }

    /**
     * Single cover image (ТЗ §6.2). Conversions run synchronously — no queue worker on shared
     * hosting (D-10).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COVER_COLLECTION)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 480, 320)
            ->nonQueued();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Published and past their publish time (ТЗ §6.2 — scheduled publishing).
     *
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published)
            ->where(function (Builder $inner) {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
