<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use Database\Factories\AlertTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale title and body of an {@see Alert} (ТЗ §9).
 *
 * @property int $id
 * @property int $alert_id
 * @property string $locale
 * @property string $title
 * @property string|null $body
 */
class AlertTranslation extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<AlertTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'alert_id',
        'locale',
        'title',
        'body',
    ];

    /**
     * @return BelongsTo<Alert, $this>
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }
}
