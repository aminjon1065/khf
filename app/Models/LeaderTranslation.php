<?php

namespace App\Models;

use Database\Factories\LeaderTranslationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stevebauman\Purify\Facades\Purify;

/**
 * Per-locale content of a {@see Leader} (ТЗ §9, §20 «г»).
 *
 * @property int $id
 * @property int $leader_id
 * @property string $locale
 * @property string $full_name
 * @property string $position
 * @property string|null $bio
 * @property string|null $reception
 */
class LeaderTranslation extends Model
{
    /** @use HasFactory<LeaderTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'leader_id',
        'locale',
        'full_name',
        'position',
        'bio',
        'reception',
    ];

    /**
     * @return BelongsTo<Leader, $this>
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(Leader::class);
    }

    /**
     * Sanitize the rich-text biography when setting it to prevent XSS (ТЗ §12.2).
     */
    protected function bio(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Purify::clean($value) : null,
        );
    }
}
