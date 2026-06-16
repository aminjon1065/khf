<?php

namespace App\Models;

use Database\Factories\TenderTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see Tender} (ТЗ §9, §20 «э»).
 *
 * @property int $id
 * @property int $tender_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $organizer
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $requirements
 * @property string|null $terms
 * @property string|null $seo_title
 * @property string|null $seo_description
 */
class TenderTranslation extends Model
{
    /** @use HasFactory<TenderTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'tender_id',
        'locale',
        'title',
        'slug',
        'organizer',
        'summary',
        'description',
        'requirements',
        'terms',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return BelongsTo<Tender, $this>
     */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /**
     * Sanitize the rich-text description when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }

    /**
     * Sanitize the rich-text participation requirements when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function requirements(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }

    /**
     * Sanitize the rich-text terms when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function terms(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
