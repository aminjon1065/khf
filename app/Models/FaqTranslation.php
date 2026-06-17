<?php

namespace App\Models;

use Database\Factories\FaqTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see Faq} (ТЗ §9, §20 «й»).
 *
 * @property int $id
 * @property int $faq_id
 * @property string $locale
 * @property string $question
 * @property string|null $answer
 */
class FaqTranslation extends Model
{
    /** @use HasFactory<FaqTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'faq_id',
        'locale',
        'question',
        'answer',
    ];

    /**
     * @return BelongsTo<Faq, $this>
     */
    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class);
    }

    /**
     * Sanitize the rich-text answer when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function answer(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
