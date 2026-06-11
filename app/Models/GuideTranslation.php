<?php

namespace App\Models;

use Database\Factories\GuideTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale content of a {@see Guide} (ТЗ §6.5, §9).
 *
 * @property int $id
 * @property int $guide_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string|null $content
 * @property string|null $seo_title
 * @property string|null $seo_description
 */
class GuideTranslation extends Model
{
    /** @use HasFactory<GuideTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'guide_id',
        'locale',
        'title',
        'slug',
        'summary',
        'content',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return BelongsTo<Guide, $this>
     */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
