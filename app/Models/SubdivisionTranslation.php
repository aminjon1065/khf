<?php

namespace App\Models;

use Database\Factories\SubdivisionTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see Subdivision} (ТЗ §9, §20 «б»).
 *
 * @property int $id
 * @property int $subdivision_id
 * @property string $locale
 * @property string $name
 * @property string|null $head
 * @property string|null $functions
 * @property string|null $address
 */
class SubdivisionTranslation extends Model
{
    /** @use HasFactory<SubdivisionTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'subdivision_id',
        'locale',
        'name',
        'head',
        'functions',
        'address',
    ];

    /**
     * @return BelongsTo<Subdivision, $this>
     */
    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class);
    }

    /**
     * Sanitize the rich-text functions description when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function functions(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
