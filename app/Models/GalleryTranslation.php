<?php

namespace App\Models;

use Database\Factories\GalleryTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale content of a {@see Gallery} (ТЗ §9, §20 «ш»).
 *
 * @property int $id
 * @property int $gallery_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $description
 */
class GalleryTranslation extends Model
{
    /** @use HasFactory<GalleryTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'gallery_id',
        'locale',
        'title',
        'slug',
        'description',
    ];

    /**
     * @return BelongsTo<Gallery, $this>
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }
}
