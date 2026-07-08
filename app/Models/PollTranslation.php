<?php

namespace App\Models;

use Database\Factories\PollTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale content of a {@see Poll}.
 *
 * @property int $id
 * @property int $poll_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @property string $slug
 */
class PollTranslation extends Model
{
    /** @use HasFactory<PollTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'poll_id',
        'locale',
        'title',
        'description',
        'slug',
    ];

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }
}
