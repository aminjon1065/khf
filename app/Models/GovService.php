<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ServiceCategory;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasTranslations;
use Database\Factories\GovServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A government service in the public catalogue (ТЗ §20 «ф»).
 *
 * @property int $id
 * @property ServiceCategory $category
 * @property ContentStatus $status
 * @property bool $is_online
 * @property string|null $external_url
 * @property string|null $processing_time
 * @property string|null $fee
 * @property int $sort_order
 */
class GovService extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<GovServiceFactory> */
    use HasFactory;

    use HasRevisions;
    use HasTranslations;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'category',
        'status',
        'is_online',
        'external_url',
        'processing_time',
        'fee',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'status' => ContentStatus::class,
            'is_online' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<GovService>  $query
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
