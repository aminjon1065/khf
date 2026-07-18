<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\PollType;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\PollFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Public opinion poll (ТЗ §8, §20 «к» — опросы, в т.ч. антикоррупционная экспертиза).
 *
 * @property int $id
 * @property PollType $type
 * @property ContentStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $show_results
 * @property int $sort_order
 * @property int $votes_count
 */
class Poll extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<PollFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'type',
        'status',
        'starts_at',
        'ends_at',
        'show_results',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PollType::class,
            'status' => ContentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'show_results' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<PollOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<PollVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * @param  Builder<Poll>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    /**
     * Polls that are within their active voting window.
     *
     * @param  Builder<Poll>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where(function (Builder $inner) use ($now): void {
            $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
        })->where(function (Builder $inner) use ($now): void {
            $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
        });
    }

    public function isAcceptingVotes(): bool
    {
        if ($this->status !== ContentStatus::Published) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasEnded(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /**
     * Whether results may be shown to the public.
     */
    public function resultsVisible(bool $hasVoted = false): bool
    {
        if ($this->show_results) {
            return true;
        }

        return $hasVoted || $this->hasEnded();
    }

    /**
     * Vote counts keyed by option id.
     *
     * @return array<int, int>
     */
    public function voteCounts(): array
    {
        return $this->votes()
            ->selectRaw('poll_option_id, count(*) as total')
            ->groupBy('poll_option_id')
            ->pluck('total', 'poll_option_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    public function totalVotes(): int
    {
        return (int) $this->votes()->count();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
