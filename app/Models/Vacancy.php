<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\EmploymentType;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasSeoMeta;
use App\Models\Concerns\HasTranslations;
use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Civil-service vacancy (ТЗ §20 подпункт «н»). Multilingual fields live in `vacancy_translations`;
 * citizens submit a questionnaire online via {@see VacancyApplication} (§21).
 *
 * @property int $id
 * @property ContentStatus $status
 * @property EmploymentType $employment_type
 * @property int $positions_count
 * @property Carbon|null $published_at
 * @property Carbon|null $deadline_at
 * @property int|null $created_by
 */
class Vacancy extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<VacancyFactory> */
    use HasFactory;

    use HasSeoMeta;
    use HasTranslations;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'status',
        'employment_type',
        'positions_count',
        'published_at',
        'deadline_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'employment_type' => EmploymentType::class,
            'positions_count' => 'integer',
            'published_at' => 'datetime',
            'deadline_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<VacancyApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(VacancyApplication::class);
    }

    /**
     * Published and past their publish time (ТЗ §20 «н» — scheduled publishing).
     *
     * @param  Builder<Vacancy>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published)
            ->where(function (Builder $inner) {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Published vacancies still accepting applications (deadline today or later, or open-ended).
     *
     * @param  Builder<Vacancy>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->published()->where(function (Builder $inner) {
            $inner->whereNull('deadline_at')->orWhereDate('deadline_at', '>=', now());
        });
    }

    /**
     * Whether the application deadline (ТЗ §20 «н») has not yet passed.
     */
    public function isOpen(): bool
    {
        return $this->deadline_at === null || $this->deadline_at->endOfDay()->isFuture();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
