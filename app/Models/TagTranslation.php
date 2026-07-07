<?php

namespace App\Models;

use Database\Factories\TagTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale name and slug of a {@see Tag} (ТЗ §9).
 *
 * @property int $id
 * @property int $tag_id
 * @property string $locale
 * @property string $name
 * @property string $slug
 */
class TagTranslation extends Model
{
    /** @use HasFactory<TagTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'slug',
    ];

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
