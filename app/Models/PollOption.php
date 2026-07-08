<?php

namespace App\Models;

use Database\Factories\PollOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single answer choice within a {@see Poll}.
 *
 * @property int $id
 * @property int $poll_id
 * @property int $sort_order
 */
class PollOption extends Model
{
    /** @use HasFactory<PollOptionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'poll_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * @return HasMany<PollOptionTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PollOptionTranslation::class);
    }

    public function translation(?string $locale = null): ?PollOptionTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function upsertTranslations(array $translations): void
    {
        foreach ($translations as $locale => $attributes) {
            $this->translations()->updateOrCreate(['locale' => $locale], $attributes);
        }
    }
}
