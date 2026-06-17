<?php

namespace App\Models;

use Database\Factories\StatisticTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale content of a {@see Statistic} (ТЗ §9, §20 «у»).
 *
 * @property int $id
 * @property int $statistic_id
 * @property string $locale
 * @property string $label
 * @property string|null $unit
 */
class StatisticTranslation extends Model
{
    /** @use HasFactory<StatisticTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'statistic_id',
        'locale',
        'label',
        'unit',
    ];

    /**
     * @return BelongsTo<Statistic, $this>
     */
    public function statistic(): BelongsTo
    {
        return $this->belongsTo(Statistic::class);
    }
}
