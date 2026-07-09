<?php

namespace App\Models;

use App\Enums\MediaContainer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property MediaContainer $container
 * @property int|null $parent_id
 * @property int $sort_order
 */
class MediaFolder extends Model
{
    protected $fillable = [
        'name',
        'container',
        'parent_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'container' => MediaContainer::class,
        ];
    }

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MediaFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<MediaFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    public function resolvedContainer(): MediaContainer
    {
        if ($this->parent_id !== null) {
            return $this->parent?->resolvedContainer() ?? $this->container;
        }

        return $this->container;
    }

    /**
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];

        $children = self::query()->where('parent_id', $this->id)->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }
}
