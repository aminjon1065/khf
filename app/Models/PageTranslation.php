<?php

namespace App\Models;

use Database\Factories\PageTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see Page} (ТЗ §9).
 *
 * @property int $id
 * @property int $page_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property array|null $blocks
 * @property string|null $seo_title
 * @property string|null $seo_description
 */
class PageTranslation extends Model
{
    /** @use HasFactory<PageTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'page_id',
        'locale',
        'title',
        'slug',
        'content',
        'blocks',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Sanitize the content HTML when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
