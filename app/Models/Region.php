<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Administrative-territorial unit — oblast or district (ТЗ §6.3, Приложение Б). Name is per-locale
 * in `region_translations`; `parent_id` gives the oblast → district hierarchy.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $code
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $sort_order
 */
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'code',
        'latitude',
        'longitude',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Region, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
