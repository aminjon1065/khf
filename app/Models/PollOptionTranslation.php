<?php

namespace App\Models;

use Database\Factories\PollOptionTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale label of a {@see PollOption}.
 *
 * @property int $id
 * @property int $poll_option_id
 * @property string $locale
 * @property string $label
 */
class PollOptionTranslation extends Model
{
    /** @use HasFactory<PollOptionTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'poll_option_id',
        'locale',
        'label',
    ];

    /**
     * @return BelongsTo<PollOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }
}
