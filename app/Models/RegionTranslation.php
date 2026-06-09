<?php

namespace App\Models;

use Database\Factories\RegionTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale name of a {@see Region} (ТЗ §9).
 *
 * @property int $id
 * @property int $region_id
 * @property string $locale
 * @property string $name
 */
class RegionTranslation extends Model
{
    /** @use HasFactory<RegionTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'region_id',
        'locale',
        'name',
    ];

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
