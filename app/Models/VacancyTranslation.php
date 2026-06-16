<?php

namespace App\Models;

use Database\Factories\VacancyTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see Vacancy} (ТЗ §9, §20 «н»).
 *
 * @property int $id
 * @property int $vacancy_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $department
 * @property string|null $location
 * @property string|null $salary
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $requirements
 * @property string|null $responsibilities
 * @property string|null $seo_title
 * @property string|null $seo_description
 */
class VacancyTranslation extends Model
{
    /** @use HasFactory<VacancyTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'vacancy_id',
        'locale',
        'title',
        'slug',
        'department',
        'location',
        'salary',
        'summary',
        'description',
        'requirements',
        'responsibilities',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
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
     * Sanitize the rich-text requirements when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function requirements(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }

    /**
     * Sanitize the rich-text responsibilities when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function responsibilities(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
