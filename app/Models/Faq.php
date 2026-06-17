<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A frequently asked question (ТЗ §20 подпункт «й» — вопросы и ответы). Multilingual
 * question/answer live in `faq_translations`.
 *
 * @property int $id
 * @property ContentStatus $status
 * @property int $sort_order
 */
class Faq extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'status',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Faq>  $query
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
