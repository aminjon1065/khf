<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\StatisticFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * An official statistical indicator (ТЗ §20 подпункт «у» — официальная статистика и основные
 * показатели деятельности). The figure is locale-independent; label/unit live in
 * `statistic_translations`.
 *
 * @property int $id
 * @property ContentStatus $status
 * @property string $value
 * @property int|null $year
 * @property int $sort_order
 */
class Statistic extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<StatisticFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'status',
        'value',
        'year',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'year' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Statistic>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
