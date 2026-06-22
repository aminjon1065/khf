<?php

namespace App\Models;

use Database\Factories\PostTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see Post} (ТЗ §9).
 *
 * @property int $id
 * @property int $post_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $seo_title
 * @property string|null $seo_description
 */
class PostTranslation extends Model
{
    /** @use HasFactory<PostTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'post_id',
        'locale',
        'title',
        'slug',
        'excerpt',
        'body',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Sanitize the body HTML when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
