<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\SubdivisionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A structural subdivision of the agency (ТЗ §20 подпункт «б»). Self-referencing `parent_id`
 * builds the org hierarchy; multilingual fields live in `subdivision_translations`.
 *
 * @property int $id
 * @property ContentStatus $status
 * @property int|null $parent_id
 * @property int $sort_order
 * @property string|null $email
 * @property string|null $phone
 * @property int|null $staff_count
 */
class Subdivision extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<SubdivisionFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'status',
        'parent_id',
        'sort_order',
        'email',
        'phone',
        'staff_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
            'staff_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Subdivision, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'parent_id');
    }

    /**
     * @return HasMany<Subdivision, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Subdivision::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @param  Builder<Subdivision>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    /**
     * Top-level subdivisions (no parent).
     *
     * @param  Builder<Subdivision>  $query
     */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
