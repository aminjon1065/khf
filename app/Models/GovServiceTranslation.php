<?php

namespace App\Models;

use Database\Factories\GovServiceTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see GovService}.
 *
 * @property int $id
 * @property int $gov_service_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $eligibility
 * @property string|null $required_documents
 * @property string|null $seo_title
 * @property string|null $seo_description
 */
class GovServiceTranslation extends Model
{
    /** @use HasFactory<GovServiceTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'gov_service_id',
        'locale',
        'title',
        'slug',
        'summary',
        'description',
        'eligibility',
        'required_documents',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return BelongsTo<GovService, $this>
     */
    public function govService(): BelongsTo
    {
        return $this->belongsTo(GovService::class);
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }

    protected function eligibility(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }

    protected function requiredDocuments(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
