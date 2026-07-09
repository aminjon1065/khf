<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaFile extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    protected string $translationModel = MediaFileTranslation::class;

    protected $fillable = [
        'name',
        'alt_text',
        'focal_x',
        'focal_y',
        'user_id',
        'media_folder_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'focal_x' => 'decimal:2',
            'focal_y' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<MediaTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class);
    }

    /**
     * @return HasMany<MediaUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    /**
     * @param  list<string>  $tagNames
     */
    public function syncTags(array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->map(fn (mixed $name): string => MediaTag::normalizeName((string) $name))
            ->filter()
            ->unique()
            ->map(fn (string $name): int => MediaTag::query()->firstOrCreate(['name' => $name])->id)
            ->values()
            ->all();

        $this->tags()->sync($tagIds);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 320, 320);
    }
}
